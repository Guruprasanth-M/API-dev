<?php
declare(strict_types=1);

class FolderCreateController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->validateBearerToken()) return $error;
        if ($error = $this->requireParams(['name'])) return $error;

        $user = $this->getAuthenticatedUser();
        if (!$user) return ['status' => 'UNAUTHORIZED', 'msg' => 'User not found', 'code' => 401];

        $folder = new Folder($this->db);
        $result = $folder->create($this->param('name'), (int)$user['id']);

        $result['code'] = ($result['status'] === 'SUCCESS') ? 201 : 400;
        return $result;
    }
}
