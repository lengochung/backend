<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;
use Termwind\Components\Raw;

/**
 * hung.le
 * 03.07.2024
 */
class NoticesModel extends BaseModel {
    protected $table = 'notices';
    protected $primaryKey = 'notice_no';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'notice_no',
        'office_id',
        'status_id',
        'subject',
        'event_date',
        'building_id',
        'fuel_type',
        'facility_id',
        'facility_detail1',
        'facility_detail2',
        'user_id',
        'detail',
        'attached_file',
        'recipient_user_id',
        'recipient_group_id',
        'broadcast_user_id',
        'broadcast_datetime',
        'daily_report_trans_user_id',
        'daily_report_trans_datetime',
        'close_user_id',
        'close_datetime'
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'event_date' => 'date:'.DATE_FORMAT,
        'close_datetime' => 'date:'.DATE_TIME_FORMAT,
    ];

    /**
     * use for getErrors multi edit
     */
    public $tablePublic = 'notices';

    /**
     * Get all notices list
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     */
    public function getAllList($cond = null, $isPaginate = true) {
        try {
            $this->writeLog(__METHOD__);

            $where = [];
            $query = $this->from("{$this->table} as n")
            ->select([
                'n.notice_no',
                'n.office_id',
                'n.status_id',
                'n.subject',
                'n.event_date',
                'n.building_id',
                'n.fuel_type',
                'n.facility_id',
                'n.facility_detail1',
                'n.facility_detail2',
                'n.user_id',
                'n.detail',
                'n.attached_file',
                'n.recipient_user_id',
                'n.recipient_group_id',
                'n.broadcast_user_id',
                'n.broadcast_datetime',
                'n.daily_report_trans_user_id',
                'n.daily_report_trans_datetime',
                'n.close_user_id',
                'n.close_datetime',
                /**
                 * Extra field, not exists in table
                 */
                'o.office_subname',
                'd.candidate',
            ])->leftJoin("offices AS o", 'o.office_id', '=', "n.office_id")
            -> leftJoin("divisions AS d", 'd.division_id', '=', "n.status_id")
            -> where($where)
            -> where("n.is_deleted", DELETED_STATUS['NOT_DELETED'])
            -> orderBy("n.notice_no", $this->ASC);

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
     * Get size all notices list
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     */
    public function getAllListSize($cond = null, $isPaginate = true) {
        try {
            $this->writeLog(__METHOD__);

            $where = [];
            $query = $this->from("{$this->table} as n")
	            ->selectRaw('count(*) as count')
	            ->leftJoin("offices AS o", 'o.office_id', '=', "n.office_id")
	            -> leftJoin("divisions AS d", 'd.division_id', '=', "n.status_id")
	            -> where($where)
	            -> where("n.is_deleted", DELETED_STATUS['NOT_DELETED'])
	            -> orderBy("n.notice_no", $this->ASC);

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query->count();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return 0;
        }
    }
    /**
     * Get all notices list
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     */
    public function getAllListFromFilterBox($cond = null, $isPaginate = true) {
        try {
            $this->writeLog(__METHOD__);
            $columnFilter = [
                'subject',
                'notice_no',
                'office_subname',
                'candidate',
                'send_type',
                'event_date',
                'close_datetime',
            ];
            //
            $send_type = NOTICE_SEND_TYPE['USER_LOGIN'];
            $receive_type = NOTICE_SEND_TYPE['NOT_USER_LOGIN'];
            //The page number for pagination
            $page = !empty($cond['page']) ? $cond['page'] : 0;
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $sort = !empty($cond['sort']) ? $cond["sort"] : null;
            $filter = !empty($cond['filter']) ? $cond["filter"] : null;
            $search = !empty($cond['search']) ? $cond["search"] : null;

            $user = $this->getUserLogin();
            $where = [
                ['n.office_id', '=', $user['office_id']],
                ['n.is_deleted', '=', DELETED_STATUS['NOT_DELETED']],
            ];
            $query = $this->from("{$this->table} as n")
                ->select([
                    'n.notice_no',
                    'n.office_id',
                    'n.status_id',
                    'n.subject',
                    'n.event_date',
                    'n.user_id',
                    'n.detail',
                    'n.broadcast_user_id',
                    'n.broadcast_datetime',
                    'n.daily_report_trans_user_id',
                    'n.daily_report_trans_datetime',
                    'n.close_user_id',
                    'n.close_datetime',
                    /**
                     * Extra field, not exists in table
                     */
                    'o.office_subname',
                    'd.candidate',
                    DB::raw("IF(n.user_id = {$user['user_id']}, '{$send_type}', '{$receive_type}') as send_type")
                ])->leftJoin("offices as o", 'o.office_id', '=', "n.office_id")
                -> leftJoin("divisions as d", 'd.division_id', '=', "n.status_id")
                -> leftJoin("users as us", 'n.user_id', '=', "us.user_id") // Send info
                -> leftJoin("users as ur", 'n.broadcast_user_id', '=', "ur.user_id"); // Receive info

            // Filter
            if (!empty($filter)) {
                foreach ($filter as $key => $value) {
                    if (!Lib::checkValueExistInArray($columnFilter, $key)) continue;
                    if (!(is_array($value) && count($value) > 0)) continue;
                    switch ($key) {
                        case 'subject':
                            $query->whereNotIn('n.subject', $value);
                            break;
                        case 'notice_no':
                            $query->whereNotIn('n.notice_no', $value);
                            break;
                        case 'office_subname':
                            $query->whereNotIn('o.office_subname', $value);
                            break;
                        case 'candidate':
                            $query->whereNotIn('d.candidate', $value);
                            break;
                        case 'event_date':
                            $formattedDates = array_map(function($date) {
                                return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                            }, $value);
                            $formattedDates = array_filter($formattedDates, function($date) {
                                return !is_null($date);
                            });
                            $query->whereNotIn('n.event_date', $formattedDates);
                            break;
                        case 'send_type':
                            $query->whereNotIn(DB::raw("IF(n.user_id = {$user['user_id']}, '{$send_type}', '{$receive_type}')"), $value);
                            break;
                        case 'close_datetime':
                            $formattedDates = array_map(function($date) {
                                return Lib::convertDateFormat($date, DATE_TIME_FORMAT, DATE_TIME_FORMAT_DB);
                            }, $value);
                            $formattedDates = array_filter($formattedDates, function($date) {
                                return !is_null($date);
                            });
                            $query->whereNotIn('n.close_datetime', $formattedDates);
                            break;
                    }
                }
            }

            if (!empty($search)) {
                foreach ($search as $key => $value) {
                    if (!Lib::checkValueExistInArray($columnFilter, $key)) continue;
                    if (Lib::isBlank($value)) continue;
                    $keyword = Lib::asteriskSearch($value);
                    switch ($key) {
                        case 'subject':
                            $query->where('n.subject', 'LIKE', "{$keyword}");
                            break;
                        case 'notice_no':
                            $query->where('n.notice_no', '=', $value);
                            break;
                        case 'office_subname':
                            $query->where('o.office_subname', 'LIKE', "{$keyword}");
                            break;
                        case 'candidate':
                            $query->where('d.candidate', 'LIKE', "{$keyword}");
                            break;
                        case 'send_type':
                            $query->whereRaw("IF(n.user_id = {$user['user_id']}, '{$send_type}', '{$receive_type}') LIKE ?", "{$keyword}");
                            break;
                        case 'event_date':
                            if (str_contains($keyword, "/")) {
                                $keyword = str_replace('/', '-', $keyword);
                            }
                            $query->where('n.event_date', 'LIKE', "{$keyword}");
                            break;
                        case 'close_datetime':
                            if (str_contains($keyword, "/")) {
                                $keyword = str_replace('/', '-', $keyword);
                            }
                            $query->where('n.close_datetime', 'LIKE', "{$keyword}");
                            break;
                    }
                }
            }

            /**
             * Search by keyword
             */
            if(!empty($cond['keyword'])) {
                $query = $query-> join("group-members as gm", function($join) {
                    $join->on('gm.user_id', '=', "n.user_id");
                    $join->where('gm.is_deleted', '=', DELETED_STATUS['NOT_DELETED']); // Get records not deleted yet
                })
                ->join("groups as g", function($join) {
                    $join->on('g.group_id', '=', 'gm.group_id');
                    $join->where('g.is_deleted', '=', DELETED_STATUS['NOT_DELETED']); // Get records not deleted yet
                })
                ->join("users as u", function($join) {
                    $join->on('u.user_id', '=', 'gm.user_id');
                    $join->where('u.is_deleted', '=', DELETED_STATUS['NOT_DELETED']); // Get records not deleted yet
                });
                $keyword = Lib::asteriskSearch($cond['keyword']);
                $query = $query->where(function($or) use ($keyword) {
                            $or->orWhere('g.group_name', 'LIKE', "{$keyword}"); // group name
                            $or->orWhere('g.description', 'LIKE', "{$keyword}"); // group description
                            $or->orWhereRaw("CONCAT(u.user_first_name, ' ', u.user_last_name) LIKE ?", [$keyword]); // User fullname
                        })->distinct(); // DISTINCT record, because of each user possibility exists in more than 1 group
            }

            $query = $query->where($where);
            // SORT
            if (!empty($sort)) {
                if (Lib::checkValueExistInArray($columnFilter, $sort['column']) && Lib::checkValueExistInArray(ORDER_DIRECTION, strtolower($sort['direction']))) {
                    $query->orderBy($sort['column'], $sort['direction']);
                }
            }

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
     * Get all notices list
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     */
    public function getAllListFromFilterBoxSize($cond = null) {
        try {
            $this->writeLog(__METHOD__);
            $columnFilter = [
                'subject',
                'notice_no',
                'office_subname',
                'candidate',
                'send_type',
                'event_date',
                'close_datetime',
            ];
            $send_type = NOTICE_SEND_TYPE['USER_LOGIN'];
            $receive_type = NOTICE_SEND_TYPE['NOT_USER_LOGIN'];
            //The page number for pagination
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $filter = !empty($cond['filter']) ? $cond["filter"] : null;
            $search = !empty($cond['search']) ? $cond["search"] : null;

            $user = $this->getUserLogin();
            $where = [
                ['n.office_id', '=', $user['office_id']],
                ['n.is_deleted', '=', DELETED_STATUS['NOT_DELETED']],
            ];

            $query = $this->from("{$this->table} as n")
                -> leftJoin("offices as o", 'o.office_id', '=', "n.office_id")
                -> leftJoin("divisions as d", 'd.division_id', '=', "n.status_id")
                -> leftJoin("users as us", 'n.user_id', '=', "us.user_id") // Send info
                -> leftJoin("users as ur", 'n.broadcast_user_id', '=', "ur.user_id"); // Receive info

            // Filter
            if (!empty($filter)) {
                foreach ($filter as $key => $value) {
                    if (!Lib::checkValueExistInArray($columnFilter, $key)) continue;
                    if (!(is_array($value) && count($value) > 0)) continue;
                    switch ($key) {
                        case 'subject':
                            $query->whereNotIn('n.subject', $value);
                            break;
                        case 'notice_no':
                            $query->whereNotIn('n.notice_no', $value);
                            break;
                        case 'candidate':
                            $query->whereNotIn('d.candidate', $value);
                            break;
                        case 'office_subname':
                            $query->whereNotIn('o.office_subname', $value);
                            break;
                        case 'send_type':
                            $query->whereNotIn(DB::raw("IF(n.user_id = {$user['user_id']}, '{$send_type}', '{$receive_type}')"), $value);
                            break;
                        case 'event_date':
                            $formattedDates = array_map(function($date) {
                                return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                            }, $value);
                            $formattedDates = array_filter($formattedDates, function($date) {
                                return !is_null($date);
                            });
                            $query->whereNotIn('n.event_date', $formattedDates);
                            break;
                        case 'close_datetime':
                            $formattedDates = array_map(function($date) {
                                return Lib::convertDateFormat($date, DATE_TIME_FORMAT, DATE_TIME_FORMAT_DB);
                            }, $value);
                            $formattedDates = array_filter($formattedDates, function($date) {
                                return !is_null($date);
                            });
                            $query->whereNotIn('n.close_datetime', $formattedDates);
                            break;
                    }
                }
            }

            if (!empty($search)) {
                foreach ($search as $key => $value) {
                    if (!Lib::checkValueExistInArray($columnFilter, $key)) continue;
                    if (Lib::isBlank($value)) continue;
                    $keyword = Lib::asteriskSearch($value);
                    switch ($key) {
                        case 'subject':
                            $query->where('n.subject', 'LIKE', "{$keyword}");
                            break;
                        case 'notice_no':
                            $query->where('n.notice_no', '=', $value);
                            break;
                        case 'office_subname':
                            $query->where('o.office_subname', '=', $value);
                            break;
                        case 'candidate':
                            $query->where('d.candidate', 'LIKE', "{$keyword}");
                            break;
                        case 'send_type':
                            $query->whereRaw("IF(n.user_id = {$user['user_id']}, '{$send_type}', '{$receive_type}') LIKE ?", "{$keyword}");
                            break;
                        case 'event_date':
                            if (str_contains($keyword, "/")) {
                                $keyword = str_replace('/', '-', $keyword);
                            }
                            $query->where('n.event_date', 'LIKE', "{$keyword}");
                            break;
                        case 'close_datetime':
                            if (str_contains($keyword, "/")) {
                                $keyword = str_replace('/', '-', $keyword);
                            }
                            $query->where('n.close_datetime', 'LIKE', "{$keyword}");
                            break;
                    }

                }
            }

            /**
             * Search by keyword
             */
            if(!empty($cond['keyword'])) {
                $query = $query-> join("group-members as gm", function($join) {
                    $join->on('gm.user_id', '=', "n.user_id");
                    $join->where('gm.is_deleted', '=', DELETED_STATUS['NOT_DELETED']); // Get records not deleted yet
                })
                ->join("groups as g", function($join) {
                    $join->on('g.group_id', '=', 'gm.group_id');
                    $join->where('g.is_deleted', '=', DELETED_STATUS['NOT_DELETED']); // Get records not deleted yet
                })
                ->join("users as u", function($join) {
                    $join->on('u.user_id', '=', 'gm.user_id');
                    $join->where('u.is_deleted', '=', DELETED_STATUS['NOT_DELETED']); // Get records not deleted yet
                });
                $keyword = Lib::asteriskSearch($cond['keyword']);
                $query = $query->where(function($or) use ($keyword) {
                            $or->orWhere('g.group_name', 'LIKE', "{$keyword}"); // group name
                            $or->orWhere('g.description', 'LIKE', "{$keyword}"); // group description
                            $or->orWhereRaw(" LIKE ?", [$keyword]); // User fullname
                        })->distinct(); // DISTINCT record, because of each user possibility exists in more than 1 group
            }

            $query->where($where);
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query->count();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return 0;
        }
    }
    /**
     * Get notice by id
     *
     * @param number $notice_no
     * @return object|null
     */
    public function getById($notice_no = null) {
        try {
            $this->writeLog(__METHOD__);
            /**
             * Condition
             */
            $officeId = $this->getUserProperty('office_id');
            $where = [
                ['n.office_id', '=', $officeId],
                ['n.notice_no', '=', $notice_no],
                ['n.is_deleted', '=', DELETED_STATUS['NOT_DELETED']]
            ];
            /**
             *
             */
            $obj = $this->from("{$this->table} as n")
            ->select([
                'n.notice_no',
                'n.office_id',
                'n.status_id',
                'n.subject',
                'n.event_date',
                'n.building_id',
                'n.fuel_type',
                'n.facility_id',
                'n.facility_detail1',
                'n.facility_detail2',
                'n.user_id',
                'n.detail',
                'n.attached_file',
                'n.recipient_user_id',
                'n.recipient_group_id',
                'n.broadcast_user_id',
                'n.broadcast_datetime',
                'n.daily_report_trans_user_id',
                'n.daily_report_trans_datetime',
                'n.close_user_id',
                'n.close_datetime',
                'n.upd_datetime',
                /**
                 * Extra field, not exists in table
                 */
                'o.office_subname'
            ])->leftJoin("offices as o", 'o.office_id', '=', "n.office_id")
            -> where($where)
            -> where("n.is_deleted", DELETED_STATUS['NOT_DELETED'])
            -> first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            /**
             *
             */
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $obj;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Insert notice
     *
     * @param object $noticeE   @var $fillable
     * @return object
     */
    public function insertData($noticeE) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            /**
             * Create new
             */
            $obj = $this->create($noticeE);
            /**
             * Recipient Users and Groups of notice
             */
            $noticeE[$this->primaryKey] = $obj->notice_no;
            $this->upsertNoticeUsersAndGroups($noticeE);
            DB::commit();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $obj;
        } catch (Exception $e) {
            // DB::rollback();
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Update notice
     * @param object $noticeE
     * @return object
     */
    public function updateData($noticeE)
    {
        try {
            $this->writeLog(__METHOD__);
            if(!$noticeE) {
                return;
            }
            DB::beginTransaction();
            /**
             * Update
             */
            $where = [
                [$this->primaryKey, $noticeE[$this->primaryKey]]
            ];
            $obj = $this
                -> where($where)
                -> first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $obj->update($noticeE);
            /**
             * Recipient Users and Groups of notice
             */
            $this->upsertNoticeUsersAndGroups($noticeE);
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
     * Delete notice by Id
     *
     * @param string $id
     * @return object
     */
    public function deleteData($id = null) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $where = [
                [$this->primaryKey, $id]
            ];
            $obj = $this
            -> where($where)
            -> first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $result = $obj->delete();

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
     * Insert or update notice-user or notice-groups when save notice
     * @param object $notice
     * @return void
     * hung.le
     */
    private function upsertNoticeUsersAndGroups ($noticeE = null) {
        $this->writeLog(__METHOD__);
        if(empty($noticeE)) return;
        /**
         * Recipient Users and Groups of notice
         */
        if(!empty($noticeE['recipient_user_ids'])) {
            $recipient_user_ids = $noticeE['recipient_user_ids'];
            $data = [];
            foreach($recipient_user_ids as $user_id) {
                array_push($data, [
                    'notice_no' => $noticeE['notice_no'],
                    'user_id' => $user_id
                ]);
            }
            $noticeUserModel = new NoticeUsersModel();
            $noticeUserModel->upsertData($data, true);
            $inputDelete = $this->assignData([], false, true, true);
            $noticeUserModel->where(function ($q) use ($noticeE, $recipient_user_ids) {
                $q->where('notice_no', '=', $noticeE['notice_no']);
                $q->whereNotIn('user_id', $recipient_user_ids);
            })->update($inputDelete);
        }

        if(!empty($noticeE['recipient_group_ids'])) {
            $recipient_group_ids = $noticeE['recipient_group_ids'];
            $data = [];
            foreach($recipient_group_ids as $group_id) {
                array_push($data, $this->assignData([
                    'notice_no' => $noticeE['notice_no'],
                    'group_id' => $group_id
                ]));
            }
            $noticeGroupModel = new NoticeGroupsModel();
            $noticeGroupModel->upsertData($data, true);
            $inputDelete = $this->assignData([], false, true, true);
            $noticeGroupModel->where(function ($q) use ($noticeE, $recipient_group_ids) {
                $q->where('notice_no', '=', $noticeE['notice_no']);
                $q->whereNotIn('group_id', $recipient_group_ids);
            })->update($inputDelete);
        }
        $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
    }

    /**
     * Get filter column notices list
     * @author hung.le
     * @date 2024/07/22
     * @param string $columnName column name
     * @return object|null
     */
    public function getFilterColumn($columnName) {
        try {
            $this->writeLog(__METHOD__);
            if (empty($columnName)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param'), 3);
                return null;
            }
            $officeId = $this->getUserProperty('office_id');
            $where = [
                ['n.office_id', '=', $officeId],
                ['n.is_deleted', '=', DELETED_STATUS['NOT_DELETED']]
            ];
            $query = $this->from("{$this->table} as n")
                ->select($columnName)
                ->leftJoin('offices as o', 'o.office_id', '=', 'n.office_id')
                ->leftJoin('divisions as d', 'd.division_id', '=', 'n.status_id')
                ->where($where)
                ->whereRaw("{$columnName} IS NOT NULL")
                ->groupBy($columnName)
                ->orderBy($columnName, 'ASC')
                ->paginate(PAGINATE_LIMIT);
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query;

        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return null;
        }
    }
}
