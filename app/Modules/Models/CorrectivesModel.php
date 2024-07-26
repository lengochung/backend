<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCompositePrimaryKey;

class CorrectivesModel extends BaseModel {
    use HasCompositePrimaryKey;
    protected $table = 'correctives';
    protected $primaryKey = ['corrective_no', 'edition_no'];
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'corrective_no',
        'edition_no',
        'office_id',
        'status',
        'notice_no',
        'close_date',
        'subject',
        'malfunction_no',
        'incident_datetime',
        'building_id',
        'fuel_type',
        'facility_id',
        'facility_detail1',
        'failicty_detail2',
        'event_id',
        'find_user',
        'detail',
        'attached_file',
        'provisional_text',
        'provisional_attached_file',
        'analysis_text',
        'analysis_attached_file',
        'correction_detail',
        'is_correction',
        'request_user_id',
        'request_date',
        'approval1_user_id',
        'approval1_datetime',
        'approval1_comment',
        'approval2_user_id',
        'approval2_datetime',
        'approval2_comment',
        'is_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'close_date' => 'date:'.DATE_TIME_FORMAT,
        'incident_datetime' => 'date:'.DATE_FORMAT,
        'request_date' => 'date:'.DATE_TIME_FORMAT,
        'approval1_datetime' => 'date:'.DATE_TIME_FORMAT,
        'approval2_datetime' => 'date:'.DATE_TIME_FORMAT,
    ];


    /**
     * Get all corrective list by filter column
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
     * @date 2024/07/22
     * @author duy.pham
     */
    public function getAllList($cond = null, $isPaginate = true)
    {
        try {
            $this->writeLog(__METHOD__);
            $columnFilter = [
                'corrective_no',
                'subject',
                'edition_no',
                'office',
                'status_name',
                'incident_datetime',
                'plan_deadline',
                'close_date',
            ];
            //The page number for pagination
            $page = !empty($cond['page']) ? $cond['page'] : 0;
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $sort = !empty($cond['sort']) ? $cond["sort"] : null;
            $filter = !empty($cond['filter']) ? $cond["filter"] : null;
            $search = !empty($cond['search']) ? $cond["search"] : null;

            $officeId = $this->getUserProperty('office_id');
            $where = [
                ['cor.office_id', '=', $officeId],
                ['cor.is_deleted', '=', DELETED_STATUS['NOT_DELETED']],
            ];

            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchByKeyword = function ($query) use ($keyword) {
                    $query->where('cor.subject', 'LIKE', $keyword);
                    $query->orWhere('cor.provisional_text', 'LIKE', $keyword);
                    $query->orWhere('cor.analysis_text', 'LIKE', $keyword);
                    $query->orWhere('cor.correction_detail', 'LIKE', $keyword);
                    $query->orWhere('d.plan_detail', 'LIKE', $keyword);
                    $query->orWhere('d.result_detail', 'LIKE', $keyword);
                };
                array_push($where, [$searchByKeyword]);
            }

            $maxEditionQuery = $this->from("{$this->table} as cor")
            ->select(
                'cor.corrective_no',
                DB::raw("MAX(cor.edition_no) as edition_no"),
                )
            ->groupBy('cor.corrective_no');

            $query = $this->from("{$this->table} as cor")
            ->joinSub($maxEditionQuery, 'cm', function($join) {
                $join->on('cm.corrective_no', '=', 'cor.corrective_no');
                $join->on('cm.edition_no', '=', 'cor.edition_no');
            })
            ->leftJoin('correctives-detail as d', function($join) {
                $join->on('d.corrective_no', '=', 'cor.corrective_no');
                $join->on('d.edition_no', '=', 'cor.edition_no');
                $join->where('d.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->leftJoin('offices as off', function($join) {
                $join->on('off.office_id', '=', 'cor.office_id');
                $join->where('off.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->leftJoin('divisions as div', function($join) {
                $join->on('div.division_id', '=', 'cor.status');
            })
            ->select(
                'cor.corrective_no',
                'cor.subject',
                'cor.edition_no',
                'cor.incident_datetime',
                'cor.close_date',
                'off.office_subname as office',
                'div.candidate as status_name',
                'd.plan_deadline',
            )
            ->where($where);

            if (!empty($sort)) {
                if (Lib::checkValueExistInArray($columnFilter, $sort['column']) && Lib::checkValueExistInArray(ORDER_DIRECTION, strtolower($sort['direction']))) {
                    switch ($sort['column']) {
                        case 'status_name':
                            $query->orderBy('div.candidate', $sort['direction']);
                            break;
                        case 'office':
                            $query->orderBy('off.office_subname', $sort['direction']);
                            break;
                        case 'plan_deadline':
                            $query->orderBy('d.plan_deadline', $sort['direction']);
                            break;
                        default:
                            $query->orderBy($sort['column'], $sort['direction']);
                            break;
                    }
                }
            }

            if (!empty($filter)) {
                foreach ($filter as $key => $value) {
                    if (Lib::checkValueExistInArray($columnFilter, $key)) {
                        if (is_array($value) && count($value) > 0) {
                            switch ($key) {
                                case 'corrective_no':
                                    $query->whereNotIn('cor.corrective_no', $value);
                                    break;
                                case 'subject':
                                    $query->whereNotIn('cor.subject', $value);
                                    break;
                                case 'edition_no':
                                    $query->whereNotIn('cor.edition_no', $value);
                                    break;
                                case 'office':
                                    $query->whereNotIn('off.office_subname', $value);
                                    break;
                                case 'status_name':
                                    $query->whereNotIn('div.candidate', $value);
                                    break;
                                case 'incident_datetime':
                                    $formattedDates = array_map(function($date) {
                                        return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                                    }, $value);
                                    $formattedDates = array_filter($formattedDates, function($date) {
                                        return !is_null($date);
                                    });
                                    $query->whereNotIn('cor.incident_datetime', $formattedDates);
                                    break;
                                case 'plan_deadline':
                                    $formattedDates = array_map(function($date) {
                                        return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                                    }, $value);
                                    $formattedDates = array_filter($formattedDates, function($date) {
                                        return !is_null($date);
                                    });
                                    $query->whereNotIn('d.plan_deadline', $formattedDates);
                                    break;
                                case 'close_date':
                                    $formattedDates = array_map(function($date) {
                                        return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                                    }, $value);
                                    $formattedDates = array_filter($formattedDates, function($date) {
                                        return !is_null($date);
                                    });
                                    $query->whereNotIn('cor.close_date', $formattedDates);
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
                                case 'corrective_no':
                                    $query->where('cor.corrective_no', 'LIKE', $keyword);
                                    break;
                                case 'subject':
                                    $query->where('cor.subject', 'LIKE', $keyword);
                                    break;
                                case 'edition_no':
                                    $query->where('cor.edition_no', 'LIKE', $keyword);
                                    break;
                                case 'office':
                                    $query->where('off.office_subname', 'LIKE', $keyword);
                                    break;
                                case 'status_name':
                                    $query->where('div.candidate', 'LIKE', $keyword);
                                    break;
                                case 'incident_datetime':
                                    if (str_contains($keyword, "/")) {
                                        $keyword = str_replace('/', '-', $keyword);
                                    }
                                    $query->where('cor.incident_datetime', 'LIKE', $keyword);
                                    break;
                                case 'plan_deadline':
                                    if (str_contains($keyword, "/")) {
                                        $keyword = str_replace('/', '-', $keyword);
                                    }
                                    $query->where('d.plan_deadline', 'LIKE', $keyword);
                                    break;
                                case 'close_date':
                                    if (str_contains($keyword, "/")) {
                                        $keyword = str_replace('/', '-', $keyword);
                                    }
                                    $query->where('cor.close_date', 'LIKE', $keyword);
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
     * Get total corrective list
     *
     * @param array $cond [
     *  'keyword' => string     keyword free search,
     *  'filter' => array       filter column
     *  'search' => array       search text in column
     * ]
     * @return object|null
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function getTotalAllList($cond = null) {
        try {
            $this->writeLog(__METHOD__);
            $columnFilter = [
                'corrective_no',
                'subject',
                'edition_no',
                'office',
                'status_name',
                'incident_datetime',
                'plan_deadline',
                'close_date',
            ];
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $filter = !empty($cond['filter']) ? $cond["filter"] : null;
            $search = !empty($cond['search']) ? $cond["search"] : null;

            $officeId = $this->getUserProperty('office_id');
            $where = [
                ['cor.office_id', '=', $officeId],
                ['cor.is_deleted', '=', DELETED_STATUS['NOT_DELETED']],
            ];

            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchByKeyword = function ($query) use ($keyword) {
                    $query->where('cor.subject', 'LIKE', $keyword);
                    $query->orWhere('cor.provisional_text', 'LIKE', $keyword);
                    $query->orWhere('cor.analysis_text', 'LIKE', $keyword);
                    $query->orWhere('cor.correction_detail', 'LIKE', $keyword);
                    $query->orWhere('d.plan_detail', 'LIKE', $keyword);
                    $query->orWhere('d.result_detail', 'LIKE', $keyword);
                };
                array_push($where, [$searchByKeyword]);
            }

            $maxEditionQuery = $this->from("{$this->table} as cor")
            ->select(
                'cor.corrective_no',
                DB::raw("MAX(cor.edition_no) as edition_no"),
                )
            ->groupBy('cor.corrective_no');

            $query = $this->from("{$this->table} as cor")
            ->joinSub($maxEditionQuery, 'cm', function($join) {
                $join->on('cm.corrective_no', '=', 'cor.corrective_no');
                $join->on('cm.edition_no', '=', 'cor.edition_no');
            })
            ->leftJoin('correctives-detail as d', function($join) {
                $join->on('d.corrective_no', '=', 'cor.corrective_no');
                $join->where('d.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->leftJoin('offices as off', function($join) {
                $join->on('off.office_id', '=', 'cor.office_id');
                $join->where('off.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->leftJoin('divisions as div', function($join) {
                $join->on('div.division_id', '=', 'cor.status');
            })
            ->select(
                'cor.corrective_no',
                'cor.subject',
                'cor.edition_no',
                'cor.incident_datetime',
                'cor.close_date',
                'off.office_subname as office',
                'div.candidate as status_name',
                'd.plan_deadline',
            )
            ->where($where);

            if (!empty($filter)) {
                foreach ($filter as $key => $value) {
                    if (Lib::checkValueExistInArray($columnFilter, $key)) {
                        if (is_array($value) && count($value) > 0) {
                            switch ($key) {
                                case 'corrective_no':
                                    $query->whereNotIn('cor.corrective_no', $value);
                                    break;
                                case 'subject':
                                    $query->whereNotIn('cor.subject', $value);
                                    break;
                                case 'edition_no':
                                    $query->whereNotIn('cor.edition_no', $value);
                                    break;
                                case 'office':
                                    $query->whereNotIn('off.office_subname', $value);
                                    break;
                                case 'status_name':
                                    $query->whereNotIn('div.candidate', $value);
                                    break;
                                case 'incident_datetime':
                                    $formattedDates = array_map(function($date) {
                                        return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                                    }, $value);
                                    $formattedDates = array_filter($formattedDates, function($date) {
                                        return !is_null($date);
                                    });
                                    $query->whereNotIn('cor.incident_datetime', $formattedDates);
                                    break;
                                case 'plan_deadline':
                                    $formattedDates = array_map(function($date) {
                                        return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                                    }, $value);
                                    $formattedDates = array_filter($formattedDates, function($date) {
                                        return !is_null($date);
                                    });
                                    $query->whereNotIn('d.plan_deadline', $formattedDates);
                                    break;
                                case 'close_date':
                                    $formattedDates = array_map(function($date) {
                                        return Lib::convertDateFormat($date, DATE_FORMAT, DATE_FORMAT_DB);
                                    }, $value);
                                    $formattedDates = array_filter($formattedDates, function($date) {
                                        return !is_null($date);
                                    });
                                    $query->whereNotIn('cor.close_date', $formattedDates);
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
                                case 'corrective_no':
                                    $query->where('cor.corrective_no', 'LIKE', $keyword);
                                    break;
                                case 'subject':
                                    $query->where('cor.subject', 'LIKE', $keyword);
                                    break;
                                case 'edition_no':
                                    $query->where('cor.edition_no', 'LIKE', $keyword);
                                    break;
                                case 'office':
                                    $query->where('off.office_subname', 'LIKE', $keyword);
                                    break;
                                case 'status_name':
                                    $query->where('div.candidate', 'LIKE', $keyword);
                                    break;
                                case 'incident_datetime':
                                    if (str_contains($keyword, "/")) {
                                        $keyword = str_replace('/', '-', $keyword);
                                    }
                                    $query->where('cor.incident_datetime', 'LIKE', $keyword);
                                    break;
                                case 'plan_deadline':
                                    if (str_contains($keyword, "/")) {
                                        $keyword = str_replace('/', '-', $keyword);
                                    }
                                    $query->where('d.plan_deadline', 'LIKE', $keyword);
                                    break;
                                case 'close_date':
                                    if (str_contains($keyword, "/")) {
                                        $keyword = str_replace('/', '-', $keyword);
                                    }
                                    $query->where('cor.close_date', 'LIKE', $keyword);
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
     * Get detail by id
     *
     * @param int $corrective_no corrective_no
     * @return object|null
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function getDetail($corrective_no = null) {
        try {
            $this->writeLog(__METHOD__);

            $where = [
                'c.corrective_no' => $corrective_no
            ];

            $maxEditionQuery = $this->from("malfunctions as mal")
            ->select(
                'mal.malfunction_no',
                DB::raw("MAX(mal.edition_no) as edition_no"),
                )
            ->groupBy('mal.malfunction_no');

            $malfunctionQuery = $this->from("malfunctions as mal")
            ->joinSub($maxEditionQuery, 'me', function($join) {
                $join->on('me.malfunction_no', '=', 'mal.malfunction_no');
                $join->on('me.edition_no', '=', 'mal.edition_no');
            })
            ->select(
                'mal.malfunction_no',
                'mal.subject'
            );


            $obj = $this->from("{$this->table} as c")
            ->leftJoin('correctives-detail as d', function($join) {
                $join->on('d.corrective_no', '=', 'c.corrective_no');
                $join->on('d.edition_no', '=', 'c.edition_no');
                $join->where('d.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->leftJoin('offices as off', function($join) {
                $join->on('off.office_id', '=', 'c.office_id');
                $join->where('off.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->leftJoin('divisions as div', function($join) {
                $join->on('div.division_id', '=', 'c.status');
            })
            ->leftJoin('users as u_request', function($join) {
                $join->on('u_request.user_id', '=', 'c.request_user_id');
            })
            ->leftJoin('users as u_ap1', function($join) {
                $join->on('u_ap1.user_id', '=', 'c.approval1_user_id');
            })
            ->leftJoin('users as u_ap2', function($join) {
                $join->on('u_ap2.user_id', '=', 'c.approval2_user_id');
            })
            ->leftJoin('users as u_plan', function($join) {
                $join->on('u_plan.user_id', '=', 'd.plan_user_id');
            })
            ->leftJoin('users as u_plan_request', function($join) {
                $join->on('u_plan_request.user_id', '=', 'd.plan_request_user_id');
            })
            ->leftJoin('users as u_plan_ap1', function($join) {
                $join->on('u_plan_ap1.user_id', '=', 'd.plan_approval1_user_id');
            })
            ->leftJoin('users as u_plan_ap2', function($join) {
                $join->on('u_plan_ap2.user_id', '=', 'd.plan_approval2_user_id');
            })
            ->leftJoin('users as u_result', function($join) {
                $join->on('u_result.user_id', '=', 'd.result_request_user_id');
            })
            ->leftJoin('users as u_result_ap1', function($join) {
                $join->on('u_result_ap1.user_id', '=', 'd.result_approval1_user_id');
            })
            ->leftJoin('users as u_result_ap2', function($join) {
                $join->on('u_result_ap2.user_id', '=', 'd.result_approval2_user_id');
            })
            ->leftJoin('items as i', function($join) {
                $join->on('i.item_no', '=', 'c.fuel_type');
            })
            ->leftJoinSub($malfunctionQuery, 'mal', function($join) {
                $join->on('mal.malfunction_no', '=', 'c.malfunction_no');
            })
            ->select(
                'c.corrective_no',
                'c.edition_no',
                'c.office_id',
                'off.office_subname as office',
                'c.status',
                'div.candidate as status_name',
                'c.notice_no',
                'c.close_date',
                'c.subject',
                'c.malfunction_no',
                'mal.subject as malfunction_subject',
                'c.incident_datetime',
                'c.building_id',
                'c.fuel_type',
                'i.item_name as fuel_type_name',
                'c.facility_id',
                'c.facility_detail1',
                'c.failicty_detail2',
                'c.event_id',
                'c.find_user',
                'c.detail',
                'c.attached_file',
                'c.provisional_text',
                'c.provisional_attached_file',
                'c.analysis_text',
                'c.analysis_attached_file',
                'c.correction_detail',
                'c.is_correction',
                'c.request_user_id',
                DB::raw("CONCAT_WS(' ', u_request.user_first_name, u_request.user_last_name) as request_user_name"),
                'c.request_date',
                'c.approval1_user_id',
                DB::raw("CONCAT_WS(' ', u_ap1.user_first_name, u_ap1.user_last_name) as approval1_user_name"),
                'c.approval1_datetime',
                'c.approval1_comment',
                'c.approval2_user_id',
                DB::raw("CONCAT_WS(' ', u_ap2.user_first_name, u_ap2.user_last_name) as approval2_user_name"),
                'c.approval2_datetime',
                'c.approval2_comment',
                'd.plan_detail',
                'd.plan_attached_file',
                'd.plan_user_id',
                DB::raw("CONCAT_WS(' ', u_plan.user_first_name, u_plan.user_last_name) as plan_user_name"),
                'd.plan_deadline',
                'd.plan_request_user_id',
                DB::raw("CONCAT_WS(' ', u_plan_request.user_first_name, u_plan_request.user_last_name) as plan_request_user_name"),
                'd.plan_request_date',
                'd.plan_approval1_user_id',
                DB::raw("CONCAT_WS(' ', u_plan_ap1.user_first_name, u_plan_ap1.user_last_name) as plan_approval1_user_name"),
                'd.plan_approval1_datetime',
                'd.plan_approval1_comment',
                'd.plan_approval2_user_id',
                DB::raw("CONCAT_WS(' ', u_plan_ap2.user_first_name, u_plan_ap2.user_last_name) as plan_approval2_user_name"),
                'd.plan_approval2_datetime',
                'd.plan_approval2_comment',
                'd.result_detail',
                'd.is_result_judgment',
                'd.result_attached_file',
                'd.result_request_user_id',
                DB::raw("CONCAT_WS(' ', u_result.user_first_name, u_result.user_last_name) as result_request_user_name"),
                'd.result_request_date',
                'd.result_approval1_user_id',
                DB::raw("CONCAT_WS(' ', u_result_ap1.user_first_name, u_result_ap1.user_last_name) as result_approval1_user_name"),
                'd.result_approval1_datetime',
                'd.result_approval1_comment',
                'd.result_approval2_user_id',
                DB::raw("CONCAT_WS(' ', u_result_ap2.user_first_name, u_result_ap2.user_last_name) as result_approval2_user_name"),
                'd.result_approval2_datetime',
                'd.result_approval2_comment',
            )->where($where)
            ->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $obj;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Insert
     *
     * @param object $corrective   corrective
     * @param object $correctiveDetail   correctiveDetail
     * @return object
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function insertData($corrective = null, $correctiveDetail = null) {
        try {
            $this->writeLog(__METHOD__);

            DB::beginTransaction();

            $rsCreateCorrective = $this->create($corrective);

            if (empty($rsCreateCorrective)) {
                DB::rollBack();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return null;
            }

            $correctiveDetail['corrective_no'] =  $corrective['corrective_no'];
            $correctiveDetail['edition_no'] =  $corrective['edition_no'];
            Lib::consoleLog($rsCreateCorrective);

            $rsCreateCorrectiveDetail = CorrectivesDetailModel::create($correctiveDetail);
            if (empty($rsCreateCorrectiveDetail)) {
                DB::rollBack();
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return null;
            }
            DB::commit();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $rsCreateCorrective;
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
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function updateData($dataE, $id) {
        try {
            $this->writeLog(__METHOD__);
            if(!$dataE || !$id) {
                return;
            }
            DB::beginTransaction();
            $where = [
                ['topic_no', $dataE['topic_no']]
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
     * Delete corrective
     *
     * @param string $id
     * @return object
     *
     * @date 2024/07/22
     * @author duy.pham
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
     * Get filter column
     *
     * @param string $columnName column name
     * @return object|null
     *
     * @date 2024/07/22
     * @author duy.pham
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

    /**
     * Get filter office
     *
     * @return object|null
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function getFilterOffice() {
        try {
            $this->writeLog(__METHOD__);

            $where = ['cor.office_id' => $this->getUserProperty('office_id')];
            $query = $this->from("{$this->table} as cor")
            ->join('offices as off', function($join) {
                $join->on('off.office_id', '=', 'cor.office_id');
                $join->where('off.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->select('off.office_subname as office')
            ->where($where)
            ->groupBy('off.office_id')
            ->orderBy('off.office_subname', 'ASC')
            ->paginate(PAGINATE_LIMIT);

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query;

        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Get filter office
     *
     * @return object|null
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function getFilterPlanDeadline() {
        try {
            $this->writeLog(__METHOD__);

            $where = ['cor.office_id' => $this->getUserProperty('office_id')];
            $query = $this->from("{$this->table} as cor")
            ->join('correctives-detail as d', function($join) {
                $join->on('d.corrective_no', '=', 'cor.corrective_no');
                $join->where('d.is_deleted', '=',  DELETED_STATUS['NOT_DELETED']);
            })
            ->select('d.plan_deadline as plan_deadline')
            ->where($where)
            ->groupBy('d.plan_deadline')
            ->orderBy('d.plan_deadline', 'DESC')
            ->paginate(PAGINATE_LIMIT);

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query;

        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Generate corrective_no for create new corrective
     *
     * @return int|null
     *
     * @date 2024/07/25
     * @author duy.pham
     */
    public function generateCorrectiveNo() {
        try {
            $this->writeLog(__METHOD__);

            $max = $this->max('corrective_no');

            if (empty($max)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return 1;
            }

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $max + 1;

        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return null;
        }
    }
}
