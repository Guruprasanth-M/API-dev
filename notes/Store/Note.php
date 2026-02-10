<?php
declare(strict_types=1);

class Note
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
     * Reload note data from DB
     */
    private function refresh(): void
    {
        $stmt = $this->db->prepare("SELECT * FROM notes WHERE id = ?");
        if (!$stmt) throw new Exception("Database error");

        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows !== 1) {
            throw new Exception("Note not found");
        }

        $this->data = $result->fetch_assoc();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->data['title'] ?? null;
    }

    public function getBody(): ?string
    {
        return $this->data['body'] ?? null;
    }

    public function getFolderId(): ?int
    {
        return isset($this->data['folder_id']) ? (int)$this->data['folder_id'] : null;
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
     * Verify that the given user owns this note
     */
    public function verifyOwner(int $user_id): bool
    {
        return $this->getOwnerId() === $user_id;
    }

    /**
     * Create a new note inside a folder
     */
    public function create(string $title, string $body, int $folder_id, int $owner_id): array
    {
        $title = trim($title);
        if (strlen($title) < 1 || strlen($title) > 255) {
            return ['status' => 'FAILED', 'error' => 'Title must be 1-255 characters'];
        }

        // Verify folder exists and belongs to user
        $folder = new Folder($this->db, $folder_id);
        if (!$folder->verifyOwner($owner_id)) {
            return ['status' => 'FAILED', 'error' => 'Unauthorized: folder does not belong to you'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO notes (title, body, folder_id, owner_id) VALUES (?, ?, ?, ?)"
        );
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("ssii", $title, $body, $folder_id, $owner_id);

        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            $stmt->close();
            $this->refresh();
            return [
                'status' => 'SUCCESS',
                'msg' => 'Note created',
                'note' => $this->toArray()
            ];
        }

        $stmt->close();
        return ['status' => 'FAILED', 'error' => 'Failed to create note'];
    }

    /**
     * Get full note data
     */
    public function get(): array
    {
        return [
            'status' => 'SUCCESS',
            'note' => $this->toArray()
        ];
    }

    /**
     * Edit note title and/or body
     */
    public function edit(?string $title = null, ?string $body = null): array
    {
        $updates = [];
        $types = '';
        $values = [];

        if ($title !== null) {
            $title = trim($title);
            if (strlen($title) < 1 || strlen($title) > 255) {
                return ['status' => 'FAILED', 'error' => 'Title must be 1-255 characters'];
            }
            $updates[] = 'title = ?';
            $types .= 's';
            $values[] = $title;
        }

        if ($body !== null) {
            $updates[] = 'body = ?';
            $types .= 's';
            $values[] = $body;
        }

        if (empty($updates)) {
            return ['status' => 'FAILED', 'error' => 'Nothing to update. Send title and/or body.'];
        }

        $query = "UPDATE notes SET " . implode(', ', $updates) . " WHERE id = ?";
        $types .= 'i';
        $values[] = $this->id;

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            $stmt->close();
            $this->refresh();
            return [
                'status' => 'SUCCESS',
                'msg' => 'Note updated',
                'note' => $this->toArray()
            ];
        }

        $stmt->close();
        return ['status' => 'FAILED', 'error' => 'Failed to update note'];
    }

    /**
     * Delete this note
     */
    public function delete(): array
    {
        $stmt = $this->db->prepare("DELETE FROM notes WHERE id = ?");
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("i", $this->id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            return ['status' => 'SUCCESS', 'msg' => 'Note deleted'];
        }

        $stmt->close();
        return ['status' => 'FAILED', 'error' => 'Failed to delete note'];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
            'folder_id' => $this->getFolderId(),
            'owner_id' => $this->getOwnerId(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt()
        ];
    }
}
