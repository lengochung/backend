<?php

namespace App\Modules\Models;

use Exception;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Common\Models\BaseModel;

class RoleModel extends BaseModel
{
    protected $table = 'roles';
    protected $primaryKey = 'role_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_id',
        'office_id',
        'role_name',
        'read_role',
        'notice_role',
        'update_role',
        'approval_role',
        'manager_role',
        'leader_role',
        'admin_role',
        'is_deleted',
    ];

    /**
     * Get all role list
     *
     * @param array $cond [
     *  'group_office_id' => int,
     *  'office_id' => int
     * ]
     * @return object|null
     */
    public function getAllList($cond = null)
    {
        try {
            $this->writeLog(__METHOD__);

            $groupOfficeId = !empty($cond['group_office_id']) ? $cond['group_office_id'] : 0;
            $officeId = !empty($cond['office_id']) ? $cond['office_id'] : 0;

            $where = [
                ['r.is_deleted', '=', DELETED_STATUS['NOT_DELETED']]
            ];
            if ($officeId) {
                array_push($where, ['r.office_id', '=', $officeId]);
            }
            if ($groupOfficeId) {
                array_push($where, ['off.group_office_id', '=', $groupOfficeId]);
            }

            $result = $this->from("{$this->table} as r")
            ->select(
                'r.role_id',
                'r.office_id',
                'r.role_name',
                'r.read_role',
                'r.notice_role',
                'r.update_role',
                'r.approval_role',
                'r.manager_role',
                'r.leader_role',
                'r.admin_role',
                'r.upd_datetime',
            )
            ->leftJoin("offices as off", 'off.office_id', '=', 'r.office_id')
            ->where($where)
            ->get();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $result;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * get record by primary key
     *
     * @param int $id    role id
     * @param boolean $includeDeleted    true: get exists record, false: get only record is_deleted = 0, default: false
     *
     * @return null|object
     */
    public function getById($id = null, $includeDeleted = false)
    {
        try {
            $this->writeLog(__METHOD__);
            if (!$id) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $query = $this->where('role_id', $id);
            if (!$includeDeleted) {
                $query->where('is_deleted', DELETED_STATUS['NOT_DELETED']);
            }
            $obj = $query->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $obj;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return;
        }
    }

    /**
     * check duplicate role name in office
     *
     * @param int $officeId    office id
     * @param string $roleName    role name
     * @param int $roleId    role id
     *
     * @return boolean|null
     */
    public function isDuplicateRoleName($officeId, $roleName, $roleId = null)
    {
        try {
            $this->writeLog(__METHOD__);
            if (empty($officeId) || empty($roleName)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $where = [
                ['office_id', '=', $officeId],
                ['is_deleted', '=', DELETED_STATUS['NOT_DELETED']],
            ];

            if ($roleId) {
                array_push($where, ['role_id', '<>', $roleId]);
            }

            $result = $this
                ->where($where)
                ->whereRaw('LOWER(`role_name`) LIKE ?', [trim(strtolower($roleName))])
                ->exists();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $result;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return;
        }
    }

    /**
     * Insert
     *
     * @param object $dataE   @var $fillable
     * @return object
     */
    public function insertData($dataE)
    {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $obj = $this->create($dataE);
            DB::commit();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $obj;
        } catch (Exception $e) {
            DB::rollback();
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }
    /**
     * Update
     *
     * @param object $dataE
     * @param string $id
     * @return object
     */
    public function updateData($dataE, $id)
    {
        try {
            $this->writeLog(__METHOD__);
            if(!$dataE || !$id) {
                return;
            }
            DB::beginTransaction();
            $where = [
                ['role_id', $id]
            ];
            $obj = $this
            -> where($where)
            -> first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $obj->update($dataE);
            DB::commit();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $obj;
        } catch (Exception $e) {
            DB::rollback();
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }
    /**
     * Delete
     *
     * @param string $id
     * @return object
     */
    public function deleteData($id = null)
    {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $where = [
                ['role_id', $id]
            ];
            $obj = $this
            -> where($where)
            -> first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $result = $obj->delete();

            $isUserRoleExists = UserRoleModel::where('role_id', $id)->exists();
            if ($isUserRoleExists) {
                $userRole = UserRoleModel::where('role_id', $id)->delete();
                if (!$userRole) {
                    DB::rollback();
                    $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                    return;
                }
            }
            DB::commit();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

}
