<?php
declare(strict_types=1);

class NoteGetController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->validateBearerToken()) return $error;
        if ($error = $this->requireParams(['id'])) return $error;

        $user = $this->getAuthenticatedUser();
        if (!$user) return ['status' => 'UNAUTHORIZED', 'msg' => 'User not found', 'code' => 401];

        try {
            $note = new Note($this->db, (int)$this->param('id'));
        } catch (Exception $e) {
            return ['status' => 'FAILED', 'error' => $e->getMessage(), 'code' => 404];
        }

        if (!$note->verifyOwner((int)$user['id'])) {
            return ['status' => 'UNAUTHORIZED', 'msg' => 'This note does not belong to you', 'code' => 403];
        }

        $result = $note->get();
        $result['code'] = 200;
        return $result;
    }
}
