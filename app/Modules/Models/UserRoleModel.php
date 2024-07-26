<?php

namespace App\Modules\Models;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Common\Models\BaseModel;
use App\Traits\HasCompositePrimaryKey;

class UserRoleModel extends BaseModel
{
    use HasCompositePrimaryKey;
    protected $table = 'user-roles';
    protected $primaryKey = ['user_id', 'office_id'];
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'office_id',
        'role_id',
        'role_update_date',
        'is_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'role_update_date' => 'date:'.DATE_FORMAT,
    ];

    /**
     * Get list user role by group office id
     *
     * @param string $groupOfficeId        group office id
     * @param string $userId        user id
     * @return object
     */
    public function getUserRoleByGroupOffice($groupOfficeId = null, $userId = null)
    {
        try{
            $this->writeLog(__METHOD__);
            if(empty($groupOfficeId) || empty($userId)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $result = $this
            ->from("offices as off")
            ->select(
                'off.office_id',
                'off.group_office_id',
                'off.office_name',
                'off.office_subname',
                'urole.user_id',
                'r.role_name',
            )
            ->selectRaw("IFNULL(urole.role_id, 0) as role_id")
            ->leftJoin("{$this->table} as urole", function($join) use ($userId) {
                $join->on('urole.office_id', '=', 'off.office_id');
                $join->where('urole.user_id', '=', $userId);
            })
            ->leftJoin("roles as r", 'r.role_id', 'urole.role_id')
            ->where('off.group_office_id', $groupOfficeId)
            ->get();
            if (empty($result)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $result;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Get role for user
     *
     * @param int $officeId        office id
     * @param int $userId        user id
     * @return object
     */
    public function getUserRole($officeId = null, $userId = null)
    {
        try{
            $this->writeLog(__METHOD__);
            if(empty($officeId) || empty($userId)) {
                return;
            }
            $result = $this
            ->from("{$this->table} as urole")
            ->select(
                'r.role_id',
                'r.role_name',
                'r.read_role',
                'r.notice_role',
                'r.update_role',
                'r.approval_role',
                'r.manager_role',
                'r.leader_role',
                'r.admin_role',
            )
            ->join("roles as r", 'r.role_id', 'urole.role_id')
            ->where('urole.office_id', $officeId)
            ->where('urole.user_id', $userId)
            ->first();
            if (empty($result)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $result;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

}
