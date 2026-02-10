<?php
declare(strict_types=1);

class FolderRenameController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->validateBearerToken()) return $error;
        if ($error = $this->requireParams(['id', 'name'])) return $error;

        $user = $this->getAuthenticatedUser();
        if (!$user) return ['status' => 'UNAUTHORIZED', 'msg' => 'User not found', 'code' => 401];

        try {
            $folder = new Folder($this->db, (int)$this->param('id'));
        } catch (Exception $e) {
            return ['status' => 'FAILED', 'error' => $e->getMessage(), 'code' => 404];
        }

        if (!$folder->verifyOwner((int)$user['id'])) {
            return ['status' => 'UNAUTHORIZED', 'msg' => 'This folder does not belong to you', 'code' => 403];
        }

        $result = $folder->rename($this->param('name'));
        $result['code'] = ($result['status'] === 'SUCCESS') ? 200 : 400;
        return $result;
    }
}
