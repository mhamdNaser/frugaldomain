<?php

namespace App\Modules\User\Repositories\Interfaces;

interface PermissionsRepositoryInterface
{
    /**
     * إرجاع كل الأدوار مع إمكانية البحث والتصفح
     */
    public function getAllPermissions($search = null, $rowsPerPage = 10, $page = 1);
    public function all();
    public function create(array $data);
    public function update($id, array $data);
    public function updateRolePermissions($roleId, array $permissionIds);
    public function deleteArray(array $ids);
    public function delete($id);
}
