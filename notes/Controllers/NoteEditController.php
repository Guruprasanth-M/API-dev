<?php
declare(strict_types=1);

class NoteEditController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->validateBearerToken()) return $error;
        if ($error = $this->requireParams(['id'])) return $error;

        $user = $this->getAuthenticatedUser();
        if (!$user) return ['status' => 'UNAUTHORIZED', 'msg' => 'User not found', 'code' => 401];

        // At least one of title or body must be provided
        $title = $this->param('title') ?: null;
        $body = $this->param('body');
        // Allow empty string body (clearing note content) but not missing
        $bodyProvided = isset($this->request['body']);

        if ($title === null && !$bodyProvided) {
            return ['status' => 'FAILED', 'msg' => 'POST parameters required: "title" and/or "body"', 'code' => 400];
        }

        try {
            $note = new Note($this->db, (int)$this->param('id'));
        } catch (Exception $e) {
            return ['status' => 'FAILED', 'error' => $e->getMessage(), 'code' => 404];
        }

        if (!$note->verifyOwner((int)$user['id'])) {
            return ['status' => 'UNAUTHORIZED', 'msg' => 'This note does not belong to you', 'code' => 403];
        }

        $result = $note->edit($title, $bodyProvided ? $body : null);
        $result['code'] = ($result['status'] === 'SUCCESS') ? 200 : 400;
        return $result;
    }
}
