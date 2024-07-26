<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;

class TopicsModel extends BaseModel {
    protected $table = 'topics';
    protected $primaryKey = 'topic_no';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'topic_no',
        'office_id',
        'notice_no',
        'topic_kbn',
        'subject',
        'event_date',
        'building_id',
        'fuel_type',
        'facility_id',
        'facility_detail1',
        'facility_detail2',
        'previous_user_id',
        'today_user_id',
        'detail',
        'attached_file',
        'is_deadline',
        'deadline',
        'completion_date',
        'is_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'event_date' => 'date:'.DATE_FORMAT,
        'deadline' => 'date:'.DATE_FORMAT,
        'completion_date' => 'date:'.DATE_FORMAT,
    ];

    /**
     * Get size topics list
     *
     * @param array $cond [
     *  'keyword' => string     keyword free search,
     *  'filter' => array       filter column
     *  'search' => array       search text in column
     * ]
     * @return object|null
     *
     * @date 2024/07/15
     * @author duy.pham
     */
    public function getTotalAllList($cond = null) {
        try {
            $this->writeLog(__METHOD__);
            $columnFilter = [
                'subject',
                'notice_no',
                'status',
                'completion_date',
                'deadline',
            ];

            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : null;
            $filter = !empty($cond['filter']) ? $cond["filter"] : null;
            $search = !empty($cond['search']) ? $cond["search"] : null;

            $officeId = $this->getUserProperty('office_id');
            $where = [
                ['topic.office_id', '=', $officeId],
                ['topic.is_deleted', '=', DELETED_STATUS['NOT_DELETED']],
            ];

            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchByKeyword = function ($query) use ($keyword) {
                    $query->where('topic.subject', 'LIKE', "{$keyword}");
                    $query->orWhere('topic.detail', 'LIKE', "{$keyword}");
                };
                array_push($where, [$searchByKeyword]);
            }

            $query = $this->from("{$this->table} as topic")
            ->leftJoin('precautions as pre', function($join) {
                $join->on('pre.notice_no', '=', 'topic.notice_no');
                $join->where('pre.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->leftJoin('correctives as cor', function($join) {
                $join->on('cor.notice_no', '=', 'topic.notice_no');
                $join->where('cor.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->leftJoin('malfunctions as mal', function($join) {
                $join->on('mal.notice_no', '=', 'topic.notice_no');
                $join->where('mal.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->selectRaw('count(topic.topic_no) as count')
            ->where($where);

            if (!empty($filter)) {
                foreach ($filter as $key => $value) {
                    if (Lib::checkValueExistInArray($columnFilter, $key)) {
                        if (is_array($value) && count($value) > 0) {
                            switch ($key) {
                                case 'subject':
                                    $query->whereNotIn('topic.subject', $value);
                                    break;
                                case 'notice_no':
                                    $query->whereNotIn('topic.notice_no', $value);
                                    break;
                                case 'status':

                                    $query->whereNotIn(DB::raw("CASE
                                        WHEN pre.precaution_no is not null THEN '".TOPIC_STATUS["PRECAUTION"]."'
                                        WHEN cor.corrective_no is not null THEN '".TOPIC_STATUS["CORRECTIVE"]."'
                                        WHEN mal.malfunction_no is not null THEN '".TOPIC_STATUS["MALFUNCTION"]."'
                                        ELSE '".TOPIC_STATUS["DAILY_REPORT"]."'
                                    END"), $value);
                                    break;
                                case 'completion_date':
                                    $formattedDates = array_map(function($date) {
                                        return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                                    }, $value);
                                    $formattedDates = array_filter($formattedDates, function($date) {
                                        return !is_null($date);
                                    });
                                    $query->whereNotIn('topic.completion_date', $formattedDates);
                                    break;
                                case 'deadline':
                                    $formattedDates = array_map(function($date) {
                                        return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                                    }, $value);
                                    $formattedDates = array_filter($formattedDates, function($date) {
                                        return !is_null($date);
                                    });
                                    $query->whereNotIn('topic.deadline', $formattedDates);
                                    break;
                            }
                        }
                    }
                }
            }

            if (!empty($search)) {
                foreach ($search as $key => $value) {
                    if (Lib::checkValueExistInArray($columnFilter, $key)) {
                        if (!Lib::isBlank($value)) {
                            $keyword = Lib::asteriskSearch($value);
                            switch ($key) {
                                case 'subject':
                                    $query->where('topic.subject', 'LIKE', "{$keyword}");
                                    break;
                                case 'notice_no':
                                    $query->where('topic.notice_no', 'LIKE', "{$keyword}");
                                    break;
                                case 'status':
                                    $query->where(DB::raw("CASE
                                        WHEN pre.precaution_no is not null THEN '".TOPIC_STATUS["PRECAUTION"]."'
                                        WHEN cor.corrective_no is not null THEN '".TOPIC_STATUS["CORRECTIVE"]."'
                                        WHEN mal.malfunction_no is not null THEN '".TOPIC_STATUS["MALFUNCTION"]."'
                                        ELSE '".TOPIC_STATUS["DAILY_REPORT"]."'
                                    END"), 'LIKE', "{$keyword}");
                                    break;
                                case 'completion_date':
                                    if (str_contains($keyword, "/")) {
                                        $keyword = str_replace('/', '-', $keyword);
                                    }
                                    $query->where('topic.completion_date', 'LIKE', "{$keyword}");
                                    break;
                                case 'deadline':
                                    if (str_contains($keyword, "/")) {
                                        $keyword = str_replace('/', '-', $keyword);
                                    }
                                    $query->where('topic.deadline', 'LIKE', "{$keyword}");
                                    break;

                            }
                        }
                    }
                }
            }

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query->count();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }
    /**
     * Get topic by id
     *
     * @param number $topic_no
     * @return object|null
     * @date 2024/07/05
     * @author hung.le
     */
    public function getById($topic_no = null) {
        try {
            $this->writeLog(__METHOD__);
            /**
             * Condition
             */
            $where = [
                'topic_no' => $topic_no
            ];
            /**
             *
             */
            $obj = $this->from("{$this->table} as t")
            ->select(
                't.topic_no',
                't.office_id',
                't.notice_no',
                't.topic_kbn',
                't.subject',
                't.event_date',
                't.building_id',
                't.fuel_type',
                't.facility_id',
                't.facility_detail1',
                't.facility_detail2',
                't.previous_user_id',
                't.today_user_id',
                't.detail',
                't.attached_file',
                't.is_deadline',
                't.deadline',
                't.completion_date'
            )->where($where)
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
     * Insert topic
     *
     * @param object $topicE   @var $fillable
     * @return object
     * @date 2024/07/05
     * @author hung.le
     */
    public function insertData($topicE) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $obj = $this->create($topicE);
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
     * Update topic
     *
     * @param object $topicE
     * @param string $id
     * @return object
     * @date 2024/07/05
     * @author hung.le
     */
    public function updateData($topicE) {
        try {
            $this->writeLog(__METHOD__);
            if(!$topicE) {
                return;
            }
            DB::beginTransaction();
            $where = [
                ['topic_no', $topicE['topic_no']]
            ];
            $obj = $this
            -> where($where)
            -> first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $obj->update($topicE);
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
     * Delete topic by Id
     *
     * @param string $id
     * @return object
     * @date 2024/07/05
     * @author hung.le
     */
    public function deleteData($id = null) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $where = [
                ['topic_no', $id]
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
     * Get all topic list by filter column
     *
     * @param array $cond [
     *  'keyword' => string     keyword free search,
     *  'filter' => array       filter column
     *  'search' => array       search text in column
     *  'sort'  =>  array       order by column
     *  'page'  => int          paging
     * ]
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @return object|null
     *
     * @date 2024/07/15
     * @author duy.pham
     */
    public function getAllList($cond = null, $isPaginate = true)
    {
        try {
            $this->writeLog(__METHOD__);
            $columnFilter = [
                'subject',
                'notice_no',
                'status',
                'completion_date',
                'deadline',
            ];
            //The page number for pagination
            $page = !empty($cond['page']) ? $cond['page'] : 0;
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $sort = !empty($cond['sort']) ? $cond["sort"] : null;
            $filter = !empty($cond['filter']) ? $cond["filter"] : null;
            $search = !empty($cond['search']) ? $cond["search"] : null;

            $officeId = $this->getUserProperty('office_id');
            $where = [
                ['topic.office_id', '=', $officeId],
                ['topic.is_deleted', '=', DELETED_STATUS['NOT_DELETED']],
            ];

            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchByKeyword = function ($query) use ($keyword) {
                    $query->where('topic.subject', 'LIKE', "{$keyword}");
                    $query->orWhere('topic.detail', 'LIKE', "{$keyword}");
                };
                array_push($where, [$searchByKeyword]);
            }

            $query = $this->from("{$this->table} as topic")
            ->leftJoin('precautions as pre', function($join) {
                $join->on('pre.notice_no', '=', 'topic.notice_no');
                $join->where('pre.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->leftJoin('correctives as cor', function($join) {
                $join->on('cor.notice_no', '=', 'topic.notice_no');
                $join->where('cor.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->leftJoin('malfunctions as mal', function($join) {
                $join->on('mal.notice_no', '=', 'topic.notice_no');
                $join->where('mal.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->select(
                'topic.subject',
                'topic.notice_no',
                'topic.completion_date',
            )
            ->selectRaw("CASE WHEN topic.is_deadline = 1 THEN topic.deadline ELSE NULL END as deadline")
            ->selectRaw("CASE WHEN pre.precaution_no is not null THEN 1
                            WHEN cor.corrective_no is not null THEN 2
                            WHEN mal.malfunction_no is not null THEN 3
                            ELSE 4
                        END as status_id")
            ->selectRaw("CASE WHEN pre.precaution_no is not null THEN '".TOPIC_STATUS["PRECAUTION"]."'
                            WHEN cor.corrective_no is not null THEN '".TOPIC_STATUS["CORRECTIVE"]."'
                            WHEN mal.malfunction_no is not null THEN '".TOPIC_STATUS["MALFUNCTION"]."'
                            ELSE '".TOPIC_STATUS["DAILY_REPORT"]."'
                        END as status")
            ->selectRaw("CASE WHEN pre.precaution_no is not null THEN pre.precaution_no
                            WHEN cor.corrective_no is not null THEN cor.corrective_no
                            WHEN mal.malfunction_no is not null THEN mal.malfunction_no
                            ELSE topic.topic_no
                        END as target_id")
            ->where($where);

            if (!empty($sort)) {
                if (Lib::checkValueExistInArray($columnFilter, $sort['column']) && Lib::checkValueExistInArray(ORDER_DIRECTION, strtolower($sort['direction']))) {
                    if ($sort['column'] == 'status') {
                        $query->orderBy(DB::raw("CASE
                        WHEN pre.precaution_no is not null THEN '".TOPIC_STATUS["PRECAUTION"]."'
                        WHEN cor.corrective_no is not null THEN '".TOPIC_STATUS["CORRECTIVE"]."'
                        WHEN mal.malfunction_no is not null THEN '".TOPIC_STATUS["MALFUNCTION"]."'
                        ELSE '".TOPIC_STATUS["DAILY_REPORT"]."'
                    END"), $sort['direction']);
                    } else {
                        $query->orderBy($sort['column'], $sort['direction']);
                    }
                }
            } else {
                $query->orderByRaw('topic.completion_date IS NULL DESC');
                $query->orderBy('topic.completion_date', 'DESC');
                $query->orderByRaw('topic.deadline IS NULL DESC');
                $query->orderBy('topic.deadline', 'DESC');
                $query->orderBy('topic.notice_no', 'DESC');
            }

            if (!empty($filter)) {
                foreach ($filter as $key => $value) {
                    if (Lib::checkValueExistInArray($columnFilter, $key)) {
                        if (is_array($value) && count($value) > 0) {
                            switch ($key) {
                                case 'subject':
                                    $query->whereNotIn('topic.subject', $value);
                                    break;
                                case 'notice_no':
                                    $query->whereNotIn('topic.notice_no', $value);
                                    break;
                                case 'status':
                                    $query->whereNotIn(DB::raw("CASE
                                        WHEN pre.precaution_no is not null THEN '".TOPIC_STATUS["PRECAUTION"]."'
                                        WHEN cor.corrective_no is not null THEN '".TOPIC_STATUS["CORRECTIVE"]."'
                                        WHEN mal.malfunction_no is not null THEN '".TOPIC_STATUS["MALFUNCTION"]."'
                                        ELSE '".TOPIC_STATUS["DAILY_REPORT"]."'
                                    END"), $value);
                                    break;
                                case 'completion_date':
                                    $formattedDates = array_map(function($date) {
                                        return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                                    }, $value);
                                    $formattedDates = array_filter($formattedDates, function($date) {
                                        return !is_null($date);
                                    });
                                    $query->whereNotIn('topic.completion_date', $formattedDates);
                                    break;
                                case 'deadline':
                                    $formattedDates = array_map(function($date) {
                                        return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                                    }, $value);
                                    $formattedDates = array_filter($formattedDates, function($date) {
                                        return !is_null($date);
                                    });
                                    $query->whereNotIn('topic.deadline', $formattedDates);
                                    break;
                            }
                        }
                    }
                }
            }

            if (!empty($search)) {
                foreach ($search as $key => $value) {
                    if (Lib::checkValueExistInArray($columnFilter, $key)) {
                        if (!Lib::isBlank($value)) {
                            $keyword = Lib::asteriskSearch($value);
                            switch ($key) {
                                case 'subject':
                                    $query->where('topic.subject', 'LIKE', "{$keyword}");
                                    break;
                                case 'notice_no':
                                    $query->where('topic.notice_no', 'LIKE', "{$keyword}");
                                    break;
                                case 'status':
                                    $query->where(DB::raw("CASE
                                        WHEN pre.precaution_no is not null THEN '".TOPIC_STATUS["PRECAUTION"]."'
                                        WHEN cor.corrective_no is not null THEN '".TOPIC_STATUS["CORRECTIVE"]."'
                                        WHEN mal.malfunction_no is not null THEN '".TOPIC_STATUS["MALFUNCTION"]."'
                                        ELSE '".TOPIC_STATUS["DAILY_REPORT"]."'
                                    END"), 'LIKE', "{$keyword}");
                                    break;
                                case 'completion_date':
                                    if (str_contains($keyword, "/")) {
                                        $keyword = str_replace('/', '-', $keyword);
                                    }
                                    $query->where('topic.completion_date', 'LIKE', "{$keyword}");
                                    break;
                                case 'deadline':
                                    if (str_contains($keyword, "/")) {
                                        $keyword = str_replace('/', '-', $keyword);
                                    }
                                    $query->where('topic.deadline', 'LIKE', "{$keyword}");
                                    break;

                            }
                        }
                    }
                }
            }

            if ($isPaginate) {
                $results = $query->offset($page * PAGINATE_LIMIT)
                ->limit(PAGINATE_LIMIT)->get();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return $results;
            }
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query->get();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Get filter column
     *
     * @param string $columnName column name
     * @return object|null
     * @date 2024/07/05
     * @author hung.le
     */
    public function getFilterColumn($columnName) {
        try {
            $this->writeLog(__METHOD__);
            if (empty($columnName)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param'), 3);
                return null;
            }
            $where = ['office_id' => $this->getUserProperty('office_id')];
            $query = $this->from("{$this->table}")
            ->select($columnName)
            ->where($where)
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
