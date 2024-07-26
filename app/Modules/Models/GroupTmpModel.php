<?php

namespace App\Modules\Models;

use Exception;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;
use App\Modules\Common\Models\BaseModel;

class GroupTmpModel extends BaseModel
{
    protected $table = 'groups-tmp';
    protected $primaryKey = 'group_id';
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id',
        'group_name',
        'description',
        'request_user_id',
        'request_date',
        'approval1_user_id',
        'approval1_date',
        'approval1_comment',
        'is_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'request_date' => 'date:'.DATE_FORMAT,
        'approval1_date' => 'date:'.DATE_FORMAT,
    ];

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
            //insert table group
            $groupCreate = GroupModel::create($dataE);
            if (!$groupCreate) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $groupId = $groupCreate->group_id;

            if (!empty($dataE["member_id_list"]) && is_array($dataE["member_id_list"])) {
                foreach ($dataE["member_id_list"] as $userId) {
                    $groupMember = [
                        "user_id" => $userId,
                        "group_id" => $groupId
                    ];
                    //insert table group-members
                    $rs = GroupMemberModel::create($groupMember);
                    if (!$rs) {
                        DB::rollback();
                        $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                        return;
                    }
                    //insert table group-members-tmp
                    $rs = GroupMemberTmpModel::create($groupMember);
                    if (!$rs) {
                        DB::rollback();
                        $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                        return;
                    }
                }
            }

            //insert table groups-tmp
            $dataE["group_id"] = $groupId;
            $groupTmp = $this->create($dataE);
            if (!$groupTmp) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            DB::commit();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $groupCreate;
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
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            DB::beginTransaction();
            $where = [
                ['group_id', $id]
            ];
            $obj = $this->where($where)->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            // update
            $dataE["upd_datetime"] = Lib::toMySqlNow();
            $dataE["upd_user_id"] = $this->getUserProperty('user_id') ?? 0;
            $obj->update($dataE);
            if(!$obj) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            //update member temp
            GroupMemberTmpModel::where('group_id', $id)->delete();
            $memberIdList = $dataE["member_id_list"];
            if (!empty($memberIdList) && is_array($memberIdList)) {
                foreach ($memberIdList as $userId) {
                    $isSuccess = GroupMemberTmpModel::create([
                        'group_id' => $id,
                        'user_id' => $userId
                    ]);
                    if (!$isSuccess) {
                        DB::rollback();
                        $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                        return;
                    }
                }
            }
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
     * Cancel update
     *
     * @param int $group_id     group id
     * @return object
     */
    public function cancelUpdate($group_id)
    {
        try {
            $this->writeLog(__METHOD__);
            if(!$group_id) {
                return;
            }
            DB::beginTransaction();
            $where = [
                ['group_id', $group_id]
            ];
            $groupTmp = $this->where($where)->first();
            if (empty($groupTmp)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $group = GroupModel::where($where)->first();
            if (empty($group)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            $result = $groupTmp->update([
                'group_name' => $group->group_name,
                'description' => $group->description,
                'upd_datetime' => Lib::toMySqlNow(),
                'upd_user_id' => $this->getUserProperty('user_id') ?? 0
            ]);

            if(!$result) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            //update member temp
            $deletedRs = GroupMemberTmpModel::where('group_id', $group_id)->delete();
            if (!$deletedRs) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $memberIdList = GroupMemberModel::where('group_id', $group_id)
            ->pluck('user_id')
            ->toArray();
            if (!empty($memberIdList) && is_array($memberIdList)) {
                foreach ($memberIdList as $userId) {
                    $isSuccess = GroupMemberTmpModel::create([
                        'group_id' => $group_id,
                        'user_id' => $userId
                    ]);
                    if (!$isSuccess) {
                        DB::rollback();
                        $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                        return;
                    }
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

    /**
     * Send request
     *
     * @param int $group_id     group id
     * @return object
     */
    public function sendRequest($group_id)
    {
        try {
            $this->writeLog(__METHOD__);
            if(!$group_id) {
                return;
            }
            DB::beginTransaction();
            $where = [
                ['group_id', $group_id]
            ];
            $groupTmp = $this->where($where)->first();
            if (empty($groupTmp)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            $result = $groupTmp->update([
                'request_user_id' => $this->getUserProperty('user_id'),
                'request_date' => Lib::getCurrentDate()
            ]);
            if(!$result) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
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

    /**
     * Send request
     *
     * @param int $groupId     group id
     * @param string $comment     comment
     * @return object
     */
    public function approval($groupId, $comment = '')
    {
        try {
            $this->writeLog(__METHOD__);
            if(!$groupId) {
                return;
            }
            DB::beginTransaction();
            $where = [
                ['group_id', $groupId]
            ];
            $groupTmp = $this->where($where)->first();
            if (empty($groupTmp)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return false;
            }

            //approval 1
            if (empty($groupTmp->approval1_user_id)) {
                $result = $groupTmp->update([
                    'approval1_user_id' => $this->getUserProperty('user_id'),
                    'approval1_date' => Lib::getCurrentDate(),
                    'approval1_comment' => $comment
                ]);
                if(!$result) {
                    DB::rollback();
                    $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                    return false;
                }
            } else {
                //approval 2
                $group = GroupModel::where($where)->first();
                if (empty($group)) {
                    $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                    return false;
                }
                // update group
                $result = $group->update([
                    'group_name' => $groupTmp->group_name,
                    'description' => $groupTmp->description,
                    'request_user_id' => $groupTmp->request_user_id,
                    'request_date' => $groupTmp->request_date,
                    'approval1_user_id' => $groupTmp->approval1_user_id,
                    'approval1_date' => $groupTmp->approval1_date,
                    'approval1_comment' => $groupTmp->approval1_comment,
                    'approval2_user_id' => $this->getUserProperty('user_id'),
                    'approval2_date' => Lib::getCurrentDate(),
                    'approval2_comment' => $comment,
                    'group_add_date' => $group->group_add_date ? $group->group_add_date : Lib::getCurrentDate(),
                    'group_update_date' => $group->group_add_date ? Lib::getCurrentDate() : null,
                ]);
                if(!$result) {
                    DB::rollback();
                    $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                    return false;
                }

                //update group-temp
                $result = $groupTmp->update([
                    'request_user_id' => null,
                    'request_date' => null,
                    'approval1_user_id' => null,
                    'approval1_date' => null,
                    'approval1_comment' => null
                ]);
                if(!$result) {
                    DB::rollback();
                    $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                    return false;
                }

                //delete group member
                $result = GroupMemberModel::where($where)->delete();
                if(!$result) {
                    DB::rollback();
                    $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                    return false;
                }
                //insert group-members-tmp to group-members
                $memberList = GroupMemberTmpModel::where($where)->get();
                foreach ($memberList as $member) {
                    $createRs = GroupMemberModel::create([
                        'group_id' => $groupId,
                        'user_id' => $member->user_id
                    ]);
                    if(!$createRs) {
                        DB::rollback();
                        $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                        return false;
                    }
                }
            }

            DB::commit();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return true;
        } catch (Exception $e) {
            DB::rollback();
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Reject
     *
     * @param int $group_id     group id
     * @return object
     */
    public function reject($group_id)
    {
        try {
            $this->writeLog(__METHOD__);
            if(!$group_id) {
                return;
            }
            DB::beginTransaction();
            $where = [
                ['group_id', $group_id]
            ];
            $groupTmp = $this->where($where)->first();
            if (empty($groupTmp)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            $result = $groupTmp->update([
                'request_user_id' => null,
                'request_date' => null,
                'approval1_user_id' => null,
                'approval1_date' => null,
                'approval1_comment' => null
            ]);
            if(!$result) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
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
