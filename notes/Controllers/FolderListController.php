<?php
declare(strict_types=1);

class FolderListController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->validateBearerToken()) return $error;

        $user = $this->getAuthenticatedUser();
        if (!$user) return ['status' => 'UNAUTHORIZED', 'msg' => 'User not found', 'code' => 401];

        $folders = Folder::getAllByOwner($this->db, (int)$user['id']);

        return [
            'status' => 'SUCCESS',
            'count' => count($folders),
            'folders' => $folders,
            'code' => 200
        ];
    }
}
