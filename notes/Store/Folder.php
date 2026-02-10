<?php
declare(strict_types=1);

class Folder
{
    private mysqli $db;
    private ?int $id = null;
    private ?array $data = null;

    public function __construct(mysqli $db, ?int $id = null)
    {
        $this->db = $db;
        if ($id !== null) {
            $this->id = $id;
            $this->refresh();
        }
    }

    /**
     * Reload folder data from DB and verify ownership
     */
    private function refresh(): void
    {
        $stmt = $this->db->prepare("SELECT * FROM folders WHERE id = ?");
        if (!$stmt) throw new Exception("Database error");

        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows !== 1) {
            throw new Exception("Folder not found");
        }

        $this->data = $result->fetch_assoc();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->data['name'] ?? null;
    }

    public function getOwnerId(): ?int
    {
        return isset($this->data['owner_id']) ? (int)$this->data['owner_id'] : null;
    }

    public function getCreatedAt(): ?string
    {
        return $this->data['created_at'] ?? null;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->data['updated_at'] ?? null;
    }

    /**
     * Verify that the given user owns this folder
     */
    public function verifyOwner(int $user_id): bool
    {
        return $this->getOwnerId() === $user_id;
    }

    /**
     * Create a new folder
     */
    public function create(string $name, int $owner_id): array
    {
        $name = trim($name);
        if (strlen($name) < 1 || strlen($name) > 100) {
            return ['status' => 'FAILED', 'error' => 'Folder name must be 1-100 characters'];
        }

        $stmt = $this->db->prepare("INSERT INTO folders (name, owner_id) VALUES (?, ?)");
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("si", $name, $owner_id);

        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            $stmt->close();
            $this->refresh();
            return [
                'status' => 'SUCCESS',
                'msg' => 'Folder created',
                'folder' => $this->toArray()
            ];
        }

        $stmt->close();
        return ['status' => 'FAILED', 'error' => 'Failed to create folder'];
    }

    /**
     * List all folders for a user
     */
    public static function getAllByOwner(mysqli $db, int $owner_id): array
    {
        $stmt = $db->prepare(
            "SELECT f.*, (SELECT COUNT(*) FROM notes n WHERE n.folder_id = f.id) AS note_count 
             FROM folders f WHERE f.owner_id = ? ORDER BY f.created_at DESC"
        );
        if (!$stmt) return [];

        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $folders = [];
        while ($row = $result->fetch_assoc()) {
            $folders[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'note_count' => (int)$row['note_count'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }

        return $folders;
    }

    /**
     * Rename this folder
     */
    public function rename(string $name): array
    {
        $name = trim($name);
        if (strlen($name) < 1 || strlen($name) > 100) {
            return ['status' => 'FAILED', 'error' => 'Folder name must be 1-100 characters'];
        }

        $stmt = $this->db->prepare("UPDATE folders SET name = ? WHERE id = ?");
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("si", $name, $this->id);

        if ($stmt->execute()) {
            $stmt->close();
            $this->refresh();
            return [
                'status' => 'SUCCESS',
                'msg' => 'Folder renamed',
                'folder' => $this->toArray()
            ];
        }

        $stmt->close();
        return ['status' => 'FAILED', 'error' => 'Failed to rename folder'];
    }

    /**
     * Delete folder and all its notes (CASCADE handles notes)
     */
    public function delete(): array
    {
        $stmt = $this->db->prepare("DELETE FROM folders WHERE id = ?");
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("i", $this->id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            return ['status' => 'SUCCESS', 'msg' => 'Folder and all its notes deleted'];
        }

        $stmt->close();
        return ['status' => 'FAILED', 'error' => 'Failed to delete folder'];
    }

    /**
     * Get all notes in this folder
     */
    public function getAllNotes(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, title, body, created_at, updated_at FROM notes WHERE folder_id = ? ORDER BY updated_at DESC"
        );
        if (!$stmt) return [];

        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $notes = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $notes[] = $row;
        }

        return $notes;
    }

    /**
     * Count notes in this folder
     */
    public function countNotes(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM notes WHERE folder_id = ?");
        if (!$stmt) return 0;

        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $row = $result->fetch_assoc();
        return (int)($row['cnt'] ?? 0);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getName(),
            'owner_id' => $this->getOwnerId(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt()
        ];
    }
}
