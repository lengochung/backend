<?php

namespace App\Modules\Models;

use Exception;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;
use App\Modules\Common\Models\BaseModel;

class GroupModel extends BaseModel
{
    protected $table = 'groups';
    protected $primaryKey = 'group_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id',
        'office_id',
        'group_name',
        'description',
        'request_user_id',
        'request_date',
        'approval1_user_id',
        'approval1_date',
        'approval1_comment',
        'approval2_user_id',
        'approval2_date',
        'approval2_comment',
        'group_add_date',
        'group_update_date',
        'is_deleted'
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'request_date' => 'date:'.DATE_FORMAT,
        'approval1_date' => 'date:'.DATE_FORMAT,
        'approval2_date' => 'date:'.DATE_FORMAT,
        'group_add_date' => 'date:'.DATE_FORMAT,
        'group_update_date' => 'date:'.DATE_FORMAT,
    ];

    /**
     * get group by group id
     *
     * @param int|string $id    group id
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
            $query = $this->where('group_id', $id);
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
     * Get all group list
     *
     * @param array $cond [
     *
     * ]
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @param object $userRole    user role
     * @return object|null
     */
    public function getAllList($cond = null, $isPaginate = true, $userRole = null)
    {
        try {
            $this->writeLog(__METHOD__);
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';

            $userId = $this->getUserProperty('user_id');
            $officeId = $this->getUserProperty('office_id');

            $where = [
                ['group.office_id', '=', $officeId],
            ];

            if ($userRole) {
                if ($userRole->approval_role && $userRole->admin_role) {
                    $whereRole = function($query) use($userId) {
                        $query->whereNotNull('group.group_add_date');
                        $query->orWhereNotNull('gt.request_user_id');
                        $query->orWhere(function($sub) use($userId) {
                            $sub->whereNull('group.group_add_date');
                            $sub->where('group.add_user_id', $userId);
                        });
                    };
                    array_push($where, [$whereRole]);
                } elseif ($userRole->admin_role) {
                    $whereRole = function($query) use($userId) {
                        $query->whereNotNull('group.group_add_date');
                        $query->orWhere(function($sub) use($userId) {
                            $sub->whereNull('group.group_add_date');
                            $sub->where('group.add_user_id', $userId);
                        });
                    };
                    array_push($where, [$whereRole]);
                } elseif ($userRole->approval_role) {
                    $whereRole = function($query) {
                        $query->whereNotNull('group.group_add_date');
                        $query->orWhereNotNull('gt.request_user_id');
                    };
                    array_push($where, [$whereRole]);
                }
                else {
                    $whereNormalUser = function($query) {
                        $query->whereNotNull('group.group_add_date');
                    };
                    array_push($where, [$whereNormalUser]);
                }
            }

            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchByKeyword = function ($query) use ($keyword) {
                    $query->where('group.group_name', 'LIKE', "{$keyword}");
                    $query->orWhere('group.description', 'LIKE', "{$keyword}");
                    $query->orWhere( DB::raw('CONCAT(us.user_first_name, " ", us.user_last_name)'), 'LIKE', "{$keyword}");
                };
                array_push($where, [$searchByKeyword]);
            }

            $query = $this->from("{$this->table} as group")
            ->select(
                'group.group_id',
                'group.group_name',
                'group.description',
                'group.group_add_date',
                'group.group_update_date',
                'group.upd_datetime',
            )
            ->selectRaw('count(gm.user_id) as member_count')
            ->leftJoin("groups-tmp as gt", 'gt.group_id', '=', 'group.group_id')
            ->leftJoin("group-members as gm", 'gm.group_id', '=', 'group.group_id')
            ->leftJoin("users as us", 'us.user_id', '=', 'gm.user_id')
            ->where($where)
            ->groupBy('group.group_id');
            if ($isPaginate) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return $query->paginate(PAGINATE_LIMIT);
            }
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query->get();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Get group detail
     *
     * @param string $groupId        group id
     * @return object
     */
    public function getDetail($groupId = null) {
        try {
            $this->writeLog(__METHOD__);
            if (!$groupId) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $obj = $this->from("{$this->table} as g")
                ->select(
                    'g.group_id',
                    'g.office_id',
                    'g.group_name',
                    'g.description',
                    'g.request_user_id',
                    'g.request_date',
                    'g.approval1_user_id',
                    'g.approval1_date',
                    'g.approval1_comment',
                    'g.approval2_user_id',
                    'g.approval2_date',
                    'g.approval2_comment',
                    'g.group_add_date',
                    'g.group_update_date',
                    'tmp.group_name as group_name_tmp',
                    'tmp.description as description_tmp',
                    'tmp.request_user_id as request_user_id_tmp',
                    'tmp.request_date as request_date_tmp',
                    'tmp.approval1_user_id as approval1_user_id_tmp',
                    'tmp.approval1_date as approval1_date_tmp',
                    'tmp.approval1_comment as approval1_comment_tmp',
                    'tmp.upd_datetime as upd_datetime',
                    'tmp.add_user_id as add_user_id',
                    DB::raw("CONCAT_WS(' ', req_user.user_first_name, req_user.user_last_name) as request_user_name"),
                    DB::raw("CONCAT_WS(' ', ap_user1.user_first_name, ap_user1.user_last_name) as approval1_user_name"),
                    DB::raw("CONCAT_WS(' ', ap_user2.user_first_name, ap_user2.user_last_name) as approval2_user_name"),
                    DB::raw("CONCAT_WS(' ', req_user_tmp.user_first_name, req_user_tmp.user_last_name) as request_user_name_tmp"),
                    DB::raw("CONCAT_WS(' ', ap_user1_tmp.user_first_name, ap_user1_tmp.user_last_name) as approval1_user_name_tmp"),
                )
                ->leftJoin("groups-tmp as tmp", 'tmp.group_id', 'g.group_id')
                ->leftJoin("users as req_user", 'req_user.user_id', 'g.request_user_id')
                ->leftJoin("users as ap_user1", 'ap_user1.user_id', 'g.approval1_user_id')
                ->leftJoin("users as ap_user2", 'ap_user2.user_id', 'g.approval2_user_id')
                ->leftJoin("users as req_user_tmp", 'req_user_tmp.user_id', 'tmp.request_user_id')
                ->leftJoin("users as ap_user1_tmp", 'ap_user1_tmp.user_id', 'tmp.approval1_user_id')
                ->where('g.group_id', $groupId)
                ->where('g.is_deleted', DELETED_STATUS['NOT_DELETED'])
                ->first();
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
     * Delete
     *
     * @param int $groupId   group id
     * @return object
     */
    public function deleteData($groupId = null)
    {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $where = [
                ['group_id', $groupId]
            ];
            //delete groups
            $obj = $this
            ->where($where)
            ->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $result = $obj->delete();
            if (!$result) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            //delete groups-tmp
            $obj = GroupTmpModel::where($where)->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $result = $obj->delete();
            if (!$result) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            //delete group member
            $result = GroupMemberModel::where($where)->delete();
            if (!$result) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            //delete group member temp
            $result = GroupMemberTmpModel::where($where)->delete();
            if (!$result) {
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
