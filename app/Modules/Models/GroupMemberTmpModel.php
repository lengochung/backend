<?php

namespace App\Modules\Models;

use Exception;
use App\Traits\HasCompositePrimaryKey;
use App\Modules\Common\Models\BaseModel;

class GroupMemberTmpModel extends BaseModel
{
    use HasCompositePrimaryKey;
    protected $table = 'group-members-tmp';
    protected $primaryKey = ['group_id', 'user_id'];
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id',
        'user_id',
        'is_deleted',
    ];

    /**
     * Get member list in group
     *
     * @param int $groupId        group id
     * @return object
     */
    public function getMemberList($groupId = null)
    {
        try{
            $this->writeLog(__METHOD__);
            if(empty($groupId)) {
                return;
            }
            $result = $this
            ->from("{$this->table} as member")
            ->select(
                'member.user_id'
            )
            ->selectRaw("CONCAT_WS(' ', u.user_first_name, u.user_last_name) as user_full_name")
            ->join("users as u", 'u.user_id', 'member.user_id')
            ->where('member.group_id', $groupId)
            ->where('member.is_deleted', DELETED_STATUS['NOT_DELETED'])
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

}
