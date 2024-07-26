<?php

namespace App\Modules\Models;

use Exception;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Common\Models\BaseModel;
use App\Modules\Models\UserRoleModel;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'office_id',
        'user_first_name',
        'user_last_name',
        'employee_no',
        'affiliation',
        'position',
        'mail',
        'password',
        'password_update_date',
        'update_date',
        'is_deleted',
    ];
    /**
     * Get user info
     *
     * @param string $user_id        user id
     * @return object
     */
    public function getUserInfo($user_id = null)
    {
        try{
            $this->writeLog(__METHOD__);
            if(empty($user_id)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $result = $this
            ->from("{$this->table} as us")
            ->select(
                'us.user_id',
                'us.office_id',
                'us.user_first_name',
                'us.user_last_name',
                'us.employee_no',
                'us.affiliation',
                'us.position',
                'us.mail',
                'us.password_update_date',
                'us.add_datetime',
                'us.upd_datetime',
                'us.update_date',
                'off.group_office_id',
                'off.office_name',
                'off.office_subname',
                'urole.role_update_date',
                'urole.upd_datetime as role_upd_datetime',
                'r.role_name'
            )
            ->selectRaw("CONCAT_WS(' ', us.user_first_name, us.user_last_name) as user_fullname")
            ->selectRaw("IFNULL(urole.role_id, 0) as role_id")
            ->leftJoin('offices as off', 'us.office_id', '=', 'off.office_id')
            ->leftJoin('user-roles as urole', function($join) {
                $join->on('urole.user_id', '=', 'us.user_id');
                $join->on('urole.office_id', '=', 'us.office_id');
            })
            ->leftJoin('roles as r', 'r.role_id', '=', 'urole.role_id')
            ->where('us.user_id', $user_id)
            ->first();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $result;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Check mail exists
     *
     * @param string $mail    mail
     * @param string $userId    userId
     *
     * @return boolean
     */
    public function isMailExists($mail = null, $userId = null)
    {
        try {
            $this->writeLog(__METHOD__);
            if (!$mail) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return false;
            };

            $query = $this->where('mail', $mail);
            if ($userId) {
                $query->where('user_id', '<>', $userId);
            }
            $obj = $query->exists();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $obj;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return false;
        }
    }

    /**
     * get record by primary key
     *
     * @param int|string $id    user id
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
            $query = $this->where('user_id', $id);
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
     * Retrieves a list of users with optional conditions and pagination.
     *
     * @param array|null $cond Optional conditions for filtering the list.
     * @return \Illuminate\Support\Collection|null The list of users or null if an exception occurs.
     *
     * @throws \Exception If an error occurs during the query.
     *
     * @date 2024/07/01 09:30
     * @author duy.pham
     */
    public function getAllList($cond = null)
    {
        try {
            $this->writeLog(__METHOD__);
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            //The page number for pagination
            $page = !empty($cond['page']) ? $cond['page'] : 0;
            $where = [
                'us.office_id' => UserModel::getUserProperty('office_id')
            ];
            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchByKeyword = function ($query) use ($keyword) {
                    $query->where('us.user_first_name', 'LIKE', "{$keyword}");
                    $query->orWhere('us.user_last_name', 'LIKE', "{$keyword}");
                    $query->orWhere('us.employee_no', 'LIKE', "{$keyword}");
                    $query->orWhere('us.affiliation', 'LIKE', "{$keyword}");
                    $query->orWhere('us.position', 'LIKE', "{$keyword}");
                    $query->orWhere('us.mail', 'LIKE', "{$keyword}");
                    $query->orWhere('r.role_name', 'LIKE', "{$keyword}");
                    $query->orWhere( DB::raw('CONCAT(us.user_first_name, " ", us.user_last_name)'), 'LIKE', "{$keyword}");
                };
                array_push($where, [$searchByKeyword]);
            }

            $query = $this
            ->from("{$this->table} as us")
            ->select(
                'us.user_id',
                'us.user_first_name',
                'us.user_last_name',
                'us.employee_no',
                'us.affiliation',
                'us.position',
                'us.mail',
                'us.password_update_date',
                'us.add_datetime',
                'us.upd_datetime',
                'us.update_date',
                'urole.role_id',
                'urole.role_update_date',
                'r.role_name',
                DB::raw("CONCAT_WS(' ', us.user_first_name, us.user_last_name) as user_fullname")
            )->leftJoin('user-roles as urole', function($join) {
                $join->on('urole.user_id', '=', 'us.user_id');
                $join->on('urole.office_id', '=', 'us.office_id');
            })->leftJoin('roles as r', 'r.role_id', '=', 'urole.role_id')
            ->where($where)
            ->offset($page * PAGINATE_LIMIT)
            ->limit(PAGINATE_LIMIT)
            ->orderBy('us.user_id', 'ASC');
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
     * Retrieves the total number of users based on optional conditions.
     *
     * @param array|null $cond Optional conditions for filtering the users.
     * @return int The total count of users or 0 if an exception occurs.
     *
     * @throws \Exception If an error occurs during the query.
     *
     * @date 2024/07/01
     * @author duy.pham
     */
    public function getTotalUsers($cond = null)
    {
        try {
            $this->writeLog(__METHOD__);
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $where = [
                'us.office_id' => UserModel::getUserProperty('office_id')
            ];
            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchByKeyword = function ($query) use ($keyword) {
                    $query->where('us.user_first_name', 'LIKE', "{$keyword}");
                    $query->orWhere('us.user_last_name', 'LIKE', "{$keyword}");
                    $query->orWhere('us.employee_no', 'LIKE', "{$keyword}");
                    $query->orWhere('us.affiliation', 'LIKE', "{$keyword}");
                    $query->orWhere('us.position', 'LIKE', "{$keyword}");
                    $query->orWhere('us.mail', 'LIKE', "{$keyword}");
                    $query->orWhere('r.role_name', 'LIKE', "{$keyword}");
                    $query->orWhere( DB::raw('CONCAT(us.user_first_name, " ", us.user_last_name)'), 'LIKE', "{$keyword}");
                };
                array_push($where, [$searchByKeyword]);
            }
            $query = $this
            ->from("{$this->table} as us")
            ->leftJoin('user-roles as urole', function($join) {
                $join->on('urole.user_id', '=', 'us.user_id');
                $join->on('urole.office_id', '=', 'us.office_id');
            })->leftJoin('roles as r', 'r.role_id', '=', 'urole.role_id')
            ->selectRaw('count(us.user_id) as count')
            ->where($where);
            return $query->count();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return 0;
        }
    }

    /**
     * Get list user by array userIds
     * Show info anywhere use userIds for anybody
     * @param array $cond [
     *  'user_ids' => array number
     * ]
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @return object|null array user
     */
    public function getUserListByUserIds($cond = null, $isPaginate = false) {
        try {
            $this->writeLog(__METHOD__);
            $userIds = !empty($cond['user_ids']) ? $cond['user_ids'] : '';

            $where = [
                'us.office_id' => UserModel::getUserProperty('office_id')
            ];

            if (!empty($userIds)) {
                $searchByUserIds = function ($query) use ($userIds) {
                    $query->whereIn('us.user_id', $userIds);
                    $query->where('us.is_deleted', DELETED_STATUS['NOT_DELETED']);
                };
                array_push($where, [$searchByUserIds]);
            }

            $query = $this
                ->from("{$this->table} as us")
                ->select(
                    'us.user_id',
                    'us.user_first_name',
                    'us.user_last_name',
                    'us.employee_no',
                    'us.affiliation',
                    'us.position',
                    'us.mail',
                    'us.password_update_date',
                    'us.add_datetime',
                    'us.upd_datetime',
                    'us.update_date',
                    DB::raw("CONCAT_WS(' ', us.user_first_name, us.user_last_name) as user_fullname")
                ) ->where($where)
                  ->orderBy('us.user_id', $this->ASC);

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
     * Get list user by array groupIds
     * Show info anywhere use groupIds for anybody
     * @param array $cond [
     *  'group_ids' => array number
     * ]
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @return array|null array user
     * @author hung.le 2024/07/17 10:00
     *
     */
    public function getUserListByGroupIds($cond = null, $isPaginate = false) {
        try {
            $this->writeLog(__METHOD__);
            $group_ids = !empty($cond['group_ids']) ? $cond['group_ids'] : '';

            $where = [
                'us.office_id' => UserModel::getUserProperty('office_id')
            ];

            if (!empty($group_ids)) {
                $searchByGroupIds = function ($query) use ($group_ids) {
                    $query->whereIn('g.group_id', $group_ids);
                    $query->where('g.is_deleted', DELETED_STATUS['NOT_DELETED']);
                    $query->where('us.is_deleted', DELETED_STATUS['NOT_DELETED']);
                    $query->where('gm.is_deleted', DELETED_STATUS['NOT_DELETED']);
                };
                array_push($where, [$searchByGroupIds]);
            }

            $query = $this
                ->from("groups as g")
                ->select(
                    'us.user_id',
                    'us.user_first_name',
                    'us.user_last_name',
                    'us.employee_no',
                    'us.affiliation',
                    'us.position',
                    'us.mail',
                    'us.password_update_date',
                    'us.add_datetime',
                    'us.upd_datetime',
                    'us.update_date',
                    DB::raw("CONCAT_WS(' ', us.user_first_name, us.user_last_name) as user_fullname")
                ) ->leftJoin('group-members as gm', 'gm.group_id', '=', 'group.group_id')
                  ->leftJoin('users as us', 'us.user_id', '=', 'gm.user_id')
                  ->where($where)
                  ->orderBy('us.user_id', $this->ASC);

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
            if (!$obj) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            if (!empty($dataE['role_list']) && is_array($dataE['role_list'])) {
                $insertDate = [];
                foreach ($dataE['role_list'] as $item) {
                    if (!empty($item['role_id'])) {
                        $role = [
                            'user_id' => $obj->user_id,
                            'office_id' => $item['office_id'],
                            'role_id' => $item['role_id'],
                            'role_update_date' => Lib::getCurrentDate(),
                            'add_datetime' => Lib::toMySqlNow(),
                            'upd_datetime' => Lib::toMySqlNow(),
                            'add_user_id' => UserModel::getUserProperty('user_id'),
                            'upd_user_id' => UserModel::getUserProperty('user_id'),
                        ];
                        array_push($insertDate, $role);
                    }
                }

                if (count($insertDate) > 0) {
                    $userRole = UserRoleModel::insert($insertDate);
                    if (!$userRole) {
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
     * compare 2 user role array
     * @param object $a     userRole
     * @param object $b     userRole
     * @return int
     */
    private  function compareUserRole($a, $b) {
        if ($a['office_id'] === $b['office_id'] && $a['role_id'] === $b['role_id']) {
            return 0;
        }
        return $a['office_id'] < $b['office_id'] || ($a['office_id'] === $b['office_id'] && $a['role_id'] < $b['role_id'] ) ? -1 : 1;
    }

    /**
     * Update
     *
     * @param object $dataE                 data update
     * @param int $userId                   user id
     * @param boolen $isUpdateUserRole      is update user role - default: true
     * @return object|null
     */
    public function updateData($dataE, $userId, $isUpdateUserRole = true)
    {
        try {
            $this->writeLog(__METHOD__);
            if(!$dataE || !$userId) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            DB::beginTransaction();
            $where = [
                ['user_id', $userId]
            ];
            $obj = $this
            -> where($where)
            -> first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            $obj->update($dataE);
            if (!$obj) {
                DB::rollback();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            if ($isUpdateUserRole) {
                $userRoleList = UserRoleModel::select(
                    'office_id',
                    'role_id',
                )
                ->where('user_id', $dataE['user_id'])
                ->get()->toArray();

                $reqRoleList = array_filter($dataE['role_list'], function($obj) {
                    if (empty($obj['role_id'])) return false;
                    return true;
                });
                //check different 2 array userRole
                $diff1 = array_udiff($userRoleList, $reqRoleList, [$this, 'compareUserRole']);
                $diff2 = array_udiff($reqRoleList, $userRoleList, [$this, 'compareUserRole']);

                //diff
                if (!empty($diff1) || !empty($diff2)) {
                    //clear all
                    UserRoleModel::where('user_id', $dataE['user_id'])
                    ->delete();

                    //insert new
                    if (!empty($dataE['role_list']) && is_array($dataE['role_list'])) {
                        $insertDate = [];
                        foreach ($dataE['role_list'] as $item) {
                            if (!empty($item['role_id'])) {
                                $role = [
                                    'user_id' => $obj->user_id,
                                    'office_id' => $item['office_id'],
                                    'role_id' => $item['role_id'],
                                    'role_update_date' => Lib::getCurrentDate(),
                                    'add_datetime' => Lib::toMySqlNow(),
                                    'upd_datetime' => Lib::toMySqlNow(),
                                    'add_user_id' => UserModel::getUserProperty('user_id'),
                                    'upd_user_id' => UserModel::getUserProperty('user_id'),
                                ];
                                array_push($insertDate, $role);
                            }
                        }

                        if (count($insertDate) > 0) {
                            $userRole = UserRoleModel::insert($insertDate);
                            if (!$userRole) {
                                DB::rollback();
                                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                                return;
                            }
                        }
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
     * Delete
     *
     * @param int $userId   user id
     * @return object
     */
    public function deleteData($userId = null)
    {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $where = [
                ['user_id', $userId]
            ];
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

            $isRoleExists = UserRoleModel::where([
                'user_id' => $userId
            ])->exists();
            if ($isRoleExists) {
                $deleteResult = UserRoleModel::where([
                    'user_id' => $userId
                ])->delete();
                if (!$deleteResult) {
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
