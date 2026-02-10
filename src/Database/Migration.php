<?php
declare(strict_types=1);

/**
 * Database Migration Runner
 * 
 * Tracks each migration individually in a `migrations` table in the database.
 * Only runs migrations that haven't been executed yet.
 * Supports multiple migration paths (auth, notes, etc.)
 * 
 * Usage:
 *   Migration::run($db, '/path/to/migrations');  // run specific path
 *   Migration::run($db);                         // run default path from .env
 */
class Migration
{
    /**
     * Ensure the migrations tracking table exists
     */
    private static function ensureTable(mysqli $db): void
    {
        $db->query("
            CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL UNIQUE,
                `ran_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Check if a specific migration has already been executed
     */
    private static function hasRun(mysqli $db, string $name): bool
    {
        $stmt = $db->prepare("SELECT COUNT(*) FROM `migrations` WHERE `migration` = ?");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $count = 0;
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return $count > 0;
    }

    /**
     * Record a migration as executed
     */
    private static function record(mysqli $db, string $name): void
    {
        $stmt = $db->prepare("INSERT INTO `migrations` (`migration`) VALUES (?)");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Run all pending migrations from the given path (or default from .env)
     * 
     * @param mysqli $db         Database connection
     * @param string|null $path  Optional migrations directory path
     * @return bool              True if all migrations succeeded
     */
    public static function run(mysqli $db, ?string $path = null): bool
    {
        $migrationsPath = $path ?? ($_ENV['MIGRATIONS_PATH'] ?? '');

        if (empty($migrationsPath)) {
            error_log("Migration path not configured");
            return false;
        }

        if (!is_dir($migrationsPath)) {
            error_log("Migrations folder not found: " . $migrationsPath);
            return false;
        }

        self::ensureTable($db);

        $files = glob($migrationsPath . '/*.up.sql');
        sort($files);

        if (empty($files)) {
            return true;
        }

        try {
            foreach ($files as $file) {
                $name = basename($file); // e.g. "01_users.up.sql"

                // Skip if already run
                if (self::hasRun($db, $name)) {
                    continue;
                }

                $sql = file_get_contents($file);
                if ($sql === false) continue;

                if ($db->multi_query($sql)) {
                    do {
                        if ($result = $db->store_result()) {
                            $result->free();
                        }
                    } while ($db->more_results() && $db->next_result());
                }

                // Record it
                self::record($db, $name);
            }

            return true;
        } catch (Exception $e) {
            error_log("Migration failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rollback all migrations from the given path
     */
    public static function down(mysqli $db, ?string $path = null): bool
    {
        $migrationsPath = $path ?? ($_ENV['MIGRATIONS_PATH'] ?? '');

        $files = glob($migrationsPath . '/*.down.sql');
        rsort($files);

        if (empty($files)) return false;

        self::ensureTable($db);

        try {
            foreach ($files as $file) {
                $name = str_replace('.down.sql', '.up.sql', basename($file));

                // Only rollback if it was previously run
                if (!self::hasRun($db, $name)) {
                    continue;
                }

                $sql = file_get_contents($file);
                if ($sql === false) continue;

                if ($db->multi_query($sql)) {
                    do {
                        if ($result = $db->store_result()) {
                            $result->free();
                        }
                    } while ($db->more_results() && $db->next_result());
                }

                // Remove from tracking
                $stmt = $db->prepare("DELETE FROM `migrations` WHERE `migration` = ?");
                $stmt->bind_param('s', $name);
                $stmt->execute();
                $stmt->close();
            }

            return true;
        } catch (Exception $e) {
            error_log("Migration rollback failed: " . $e->getMessage());
            return false;
        }
    }
}
