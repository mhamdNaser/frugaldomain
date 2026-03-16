<?php

namespace App\Modules\User\Repositories\Interfaces;

interface AdminRoleRepositoryInterface
{
    /**
     * إرجاع كل الأدوار مع إمكانية البحث والتصفح
     */
    public function getAllRoles($search = null, $rowsPerPage = 10, $page = 1);
    public function allRoles();
    public function createRole(array $data);
    public function updateRole($id, array $data);
    public function updateRolePermissions($id, array $permissionIds);
    public function deleteArray(array $ids);
    public function deleteRole($id);
}
