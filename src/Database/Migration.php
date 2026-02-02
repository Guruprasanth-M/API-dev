<?php
declare(strict_types=1);

class Migration
{
    public static function run(mysqli $db): bool
    {
        $migrationsPath = $_ENV['MIGRATIONS_PATH'] ?? '';
        $migrationFlag = $_ENV['MIGRATION_FLAG'] ?? '';

        if (empty($migrationsPath) || empty($migrationFlag)) {
            error_log("Migration paths not configured in .env");
            return false;
        }

        // Already migrated
        if (file_exists($migrationFlag)) {
            return true;
        }

        if (!is_dir($migrationsPath)) {
            error_log("Migrations folder not found: " . $migrationsPath);
            return false;
        }

        $files = glob($migrationsPath . '/*.up.sql');
        sort($files);

        if (empty($files)) {
            file_put_contents($migrationFlag, date('Y-m-d H:i:s'));
            return true;
        }

        try {
            foreach ($files as $file) {
                $sql = file_get_contents($file);
                if ($sql === false) continue;

                if ($db->multi_query($sql)) {
                    do {
                        if ($result = $db->store_result()) {
                            $result->free();
                        }
                    } while ($db->more_results() && $db->next_result());
                }
            }

            file_put_contents($migrationFlag, date('Y-m-d H:i:s'));
            return true;
        } catch (Exception $e) {
            error_log("Migration failed: " . $e->getMessage());
            return false;
        }
    }

    public static function down(mysqli $db): bool
    {
        $migrationsPath = $_ENV['MIGRATIONS_PATH'] ?? '';
        $migrationFlag = $_ENV['MIGRATION_FLAG'] ?? '';

        $files = glob($migrationsPath . '/*.down.sql');
        rsort($files);

        if (empty($files)) return false;

        try {
            foreach ($files as $file) {
                $sql = file_get_contents($file);
                if ($sql === false) continue;

                if ($db->multi_query($sql)) {
                    do {
                        if ($result = $db->store_result()) {
                            $result->free();
                        }
                    } while ($db->more_results() && $db->next_result());
                }
            }

            if (file_exists($migrationFlag)) {
                unlink($migrationFlag);
            }
            return true;
        } catch (Exception $e) {
            error_log("Migration rollback failed: " . $e->getMessage());
            return false;
        }
    }

    public static function reset(): void
    {
        $migrationFlag = $_ENV['MIGRATION_FLAG'] ?? '';
        if (file_exists($migrationFlag)) {
            unlink($migrationFlag);
        }
    }
}
