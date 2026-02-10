<?php
declare(strict_types=1);

class NoteCreateController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->validateBearerToken()) return $error;
        if ($error = $this->requireParams(['title', 'body', 'folder_id'])) return $error;

        $user = $this->getAuthenticatedUser();
        if (!$user) return ['status' => 'UNAUTHORIZED', 'msg' => 'User not found', 'code' => 401];

        try {
            $note = new Note($this->db);
            $result = $note->create(
                $this->param('title'),
                $this->param('body'),
                (int)$this->param('folder_id'),
                (int)$user['id']
            );
        } catch (Exception $e) {
            return ['status' => 'FAILED', 'error' => $e->getMessage(), 'code' => 400];
        }

        $result['code'] = ($result['status'] === 'SUCCESS') ? 201 : 400;
        return $result;
    }
}
