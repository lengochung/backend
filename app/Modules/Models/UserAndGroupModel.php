<?php

namespace App\Modules\Models;

use Exception;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;
use App\Modules\Common\Models\BaseModel;

class UserAndGroupModel extends BaseModel {

    /**
     * tables
     */
    protected $usersTable = 'users';
    protected $groupsTable = 'groups';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // Not field
    ];

    public const TYPE = [
        'user' => 'user',
        'group' => 'group',
        'delimiter' => '|'
    ];

    /**
     * Get list user union group list for select by condition
     *
     * @param array $cond [
     *  'keyword' => string
     * ]
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @return object|null
     * @date 2024/07/05
     * @author hung.le
     */
    public function getUserListUnionGroupList($cond = null, $isPaginate = true) {
        try {
            $this->writeLog(__METHOD__);
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $notice_no = !empty($cond['notice_no']) ? $cond['notice_no'] : null;

            $whereUser = [
                'us.office_id' => $this->getUserProperty('office_id')
            ];
            $whereGroup = [
                'group.office_id' => $this->getUserProperty('office_id')
            ];

            /**
             * Set keyword
             */
            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchUserByKeyword = function ($query) use ($keyword) {
                    $query->where('us.user_first_name', 'LIKE', "{$keyword}");
                    $query->orWhere('us.user_last_name', 'LIKE', "{$keyword}");
                    $query->orWhere('us.employee_no', 'LIKE', "{$keyword}");
                    $query->orWhere('us.affiliation', 'LIKE', "{$keyword}");
                    $query->orWhere('us.position', 'LIKE', "{$keyword}");
                    $query->orWhere('us.mail', 'LIKE', "{$keyword}");
                    $query->orWhere('r.role_name', 'LIKE', "{$keyword}");
                    $query->orWhere( DB::raw('CONCAT(us.user_first_name, " ", us.user_last_name)'), 'LIKE', "{$keyword}");
                };
                array_push($whereUser, [$searchUserByKeyword]);
                //....
                $searchGroupByKeyword = function ($query) use ($keyword) {
                    $query->whereNotNull('group.group_add_date');
                    $query->where(function ($sub) use ($keyword) {
                        $sub->where('group.group_name', 'LIKE', "{$keyword}");
                        $sub->orWhere('group.description', 'LIKE', "{$keyword}");
                    });
                };
                array_push($whereGroup, [$searchGroupByKeyword]);
            }

            $TYPE = self::TYPE;
            /**
             * users
             */
            $userQuery = $this->from("{$this->usersTable} as us")
                ->select(
                    'us.user_id as id',
                    DB::raw("CONCAT_WS(' ', us.user_first_name, us.user_last_name) as name"),
                    'us.user_first_name as tag_name',
                    DB::raw("CONCAT(us.user_id, '{$TYPE['delimiter']}{$TYPE['user']}') as select_id")
                ) ->leftJoin('user-roles as urole', function($join) {
                    $join->on('urole.user_id', '=', 'us.user_id');
                    $join->on('urole.office_id', '=', 'us.office_id');
                })->leftJoin('roles as r', 'r.role_id', '=', 'urole.role_id')
                ->where($whereUser)
                ->where('us.is_deleted', '=', DELETED_STATUS['NOT_DELETED']);
            /**
             * groups
             */
            $groupQuery = $this->from("{$this->groupsTable} as group")
                ->select(
                    'group.group_id as id',
                    'group.group_name as name',
                    'group.group_name as tag_name',
                    DB::raw("CONCAT(group.group_id, '{$TYPE['delimiter']}{$TYPE['group']}') as select_id")
                )
                ->where($whereGroup)
                ->where('group.is_deleted', '=', DELETED_STATUS['NOT_DELETED'])
                ->groupBy('group.group_id');

            /**
             * Union
             */
            $query = $userQuery->union($groupQuery);

            /**
             * Get information users and groups by notice_no
             */
            if(!empty($notice_no)) {
                $subWhere = [
                    'notice_no' => $notice_no,
                    'is_deleted' => DELETED_STATUS['NOT_DELETED']
                ];
                $noticeUsersQuery = $this->from("notice-users")
                    ->select(
                        'user_id as id',
                        DB::raw("CONCAT(user_id, '{$TYPE['delimiter']}{$TYPE['user']}') as select_id")
                    )->where($subWhere);
                $noticeGroupsQuery = $this->from("notice-groups")
                    ->select(
                        'group_id as id',
                        DB::raw("CONCAT(group_id, '{$TYPE['delimiter']}{$TYPE['group']}') as select_id")
                    )->where($subWhere);
                $unionQuery = $noticeUsersQuery->union($noticeGroupsQuery);

                $query = $this->fromSub($query, 'sub')
                    ->select('sub.*')
                    ->joinSub($unionQuery, 'uni', function($join) {
                        $join->on('uni.id', '=', 'sub.id');
                        $join->on('uni.select_id', '=', 'sub.select_id');
                    });
            }
            // Sort name
            $query = $query->orderBy('name', $this->ASC);
            /**
             *
             */
            if($isPaginate) {
                $page = isset($cond['page']) ? $cond['page'] : 0;
                $query = $query->offset($page * PAGINATE_LIMIT)
                ->limit(PAGINATE_LIMIT);
            }
            $results = $query->get();
            $this->writeLog(__METHOD__, false);
            if (empty($results)) {
                return;
            }
            return $results;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }
    /**
     * Get list user union group list for select by condition
     *
     * @param array $cond [
     *  'keyword' => string
     * ]
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @return object|null
     * @date 2024/07/05
     * @author hung.le
     */
    public function getUserListUnionGroupListSize($cond = null) {
        try {
            $this->writeLog(__METHOD__);
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $notice_no = !empty($cond['notice_no']) ? $cond['notice_no'] : null;

            $whereUser = [
                'us.office_id' => $this->getUserProperty('office_id')
            ];
            $whereGroup = [
                'group.office_id' => $this->getUserProperty('office_id')
            ];

            /**
             * Set keyword
             */
            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchUserByKeyword = function ($query) use ($keyword) {
                    $query->where('us.user_first_name', 'LIKE', "{$keyword}");
                    $query->orWhere('us.user_last_name', 'LIKE', "{$keyword}");
                    $query->orWhere('us.employee_no', 'LIKE', "{$keyword}");
                    $query->orWhere('us.affiliation', 'LIKE', "{$keyword}");
                    $query->orWhere('us.position', 'LIKE', "{$keyword}");
                    $query->orWhere('us.mail', 'LIKE', "{$keyword}");
                    $query->orWhere('r.role_name', 'LIKE', "{$keyword}");
                    $query->orWhere( DB::raw('CONCAT(us.user_first_name, " ", us.user_last_name)'), 'LIKE', "{$keyword}");
                };
                array_push($whereUser, [$searchUserByKeyword]);
                //....
                $searchGroupByKeyword = function ($query) use ($keyword) {
                    $query->whereNotNull('group.group_add_date');
                    $query->where(function ($sub) use ($keyword) {
                        $sub->where('group.group_name', 'LIKE', "{$keyword}");
                        $sub->orWhere('group.description', 'LIKE', "{$keyword}");
                    });
                };
                array_push($whereGroup, [$searchGroupByKeyword]);
            }

            $TYPE = self::TYPE;
            /**
             * users
             */
            $userQuery = $this->from("{$this->usersTable} as us")
                ->select(
                    'us.user_id as id',
                    DB::raw("CONCAT_WS(' ', us.user_first_name, us.user_last_name) as name"),
                    'us.user_first_name as tag_name',
                    DB::raw("CONCAT(us.user_id, '{$TYPE['delimiter']}{$TYPE['user']}') as select_id")
                ) ->leftJoin('user-roles as urole', function($join) {
                    $join->on('urole.user_id', '=', 'us.user_id');
                    $join->on('urole.office_id', '=', 'us.office_id');
                })->leftJoin('roles as r', 'r.role_id', '=', 'urole.role_id')
                ->where($whereUser)
                ->where('us.is_deleted', '=', DELETED_STATUS['NOT_DELETED']);
            /**
             * groups
             */
            $groupQuery = $this->from("{$this->groupsTable} as group")
                ->select(
                    'group.group_id as id',
                    'group.group_name as name',
                    'group.group_name as tag_name',
                    DB::raw("CONCAT(group.group_id, '{$TYPE['delimiter']}{$TYPE['group']}') as select_id")
                )
                ->where($whereGroup)
                ->where('group.is_deleted', '=', DELETED_STATUS['NOT_DELETED'])
                ->groupBy('group.group_id');

            /**
             * Union
             */
            $query = $userQuery->union($groupQuery);

            /**
             * Get information users and groups by notice_no
             */
            if(!empty($notice_no)) {
                $subWhere = [
                    'notice_no' => $notice_no,
                    'is_deleted' => DELETED_STATUS['NOT_DELETED']
                ];
                $noticeUsersQuery = $this->from("notice-users")
                    ->select(
                        'user_id as id',
                        DB::raw("CONCAT(user_id, '{$TYPE['delimiter']}{$TYPE['user']}') as select_id")
                    )->where($subWhere);
                $noticeGroupsQuery = $this->from("notice-groups")
                    ->select(
                        'group_id as id',
                        DB::raw("CONCAT(group_id, '{$TYPE['delimiter']}{$TYPE['user']}') as select_id")
                    )->where($subWhere);
                $unionQuery = $noticeUsersQuery->union($noticeGroupsQuery);

                $query = $this->fromSub($query, 'sub')
                    ->select('sub.*')
                    ->joinSub($unionQuery, 'uni', function($join) {
                        $join->on('uni.id', '=', 'sub.id');
                        $join->on('uni.select_id', '=', 'sub.select_id');
                    });
            }
            // Sort name
            $query = $query->orderBy('name', $this->ASC);

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query->count();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return 0;
        }
    }
}
