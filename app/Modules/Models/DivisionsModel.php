<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;

class DivisionsModel extends BaseModel {
    public $table = 'divisions';
    protected $primaryKey = 'division_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'division_id',
        'function_id',
        'page_id',
        'item_id',
        'candidate'
    ];

    /**
     * Get all division list
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
            // Condition
            $where = [];
            if (!empty($cond['function_id'])) {
                array_push($where, ['d.function_id', '=', $cond['function_id']]);
            }
            if (!empty($cond['page_id'])) {
                array_push($where, ['d.page_id', '=', $cond['page_id']]);
            }
            if (!empty($cond['item_id'])) {
                array_push($where, ['d.item_id', '=', $cond['item_id']]);
            }
            // Query
            $query = $this->from("{$this->table} as d")
            ->select([
                'd.division_id',
                'd.function_id',
                'd.page_id',
                'd.item_id',
                'd.candidate',
                'd.upd_datetime',
                /**
                 * Extra info
                 */
                DB::raw("CONCAT(f.function_name, '@', p.page_name, '@', i.item_name) as division_name")
            ])->leftJoin('functions as f', 'f.function_no', '=', 'd.function_id')
              ->leftJoin('pages as p', 'p.page_no', '=', 'd.page_id')
              ->leftJoin('items as i', 'i.item_no', '=', 'd.item_id');

            if (!empty($cond['keyword'])) {
                $keyword = Lib::asteriskSearch($cond['keyword']);
                $query->orWhere(function ($sub) use ($keyword) {
                    $sub->where('f.function_name', 'LIKE', "{$keyword}");
                    $sub->orWhere('p.page_name', 'LIKE', "{$keyword}");
                    $sub->orWhere('i.item_name', 'LIKE', "{$keyword}");
                    $sub->orWhere('d.candidate', 'LIKE', "{$keyword}");
                });
            }
            $query->where($where)
                ->orderBy('d.function_id', 'asc')
                ->orderBy('d.page_id', 'asc')
                ->orderBy('d.item_id', 'asc')
                ->orderBy('d.candidate', 'asc');

            if($isPaginate) {
                $page = isset($cond['page']) ? $cond['page'] : 0;
                $query->offset($page * PAGINATE_LIMIT)
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
     * Get all division list size
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     */
    public function getAllListSize($cond = null) {
        try {
            $this->writeLog(__METHOD__);
            // Condition
            $where = [];
            if (!empty($cond['function_id'])) {
                array_push($where, ['d.function_id', '=', $cond['function_id']]);
            }
            if (!empty($cond['page_id'])) {
                array_push($where, ['d.page_id', '=', $cond['page_id']]);
            }
            if (!empty($cond['item_id'])) {
                array_push($where, ['d.item_id', '=', $cond['item_id']]);
            }
            // Query
            $query = $this->from("{$this->table} as d")
                ->selectRaw('count(*) as count')
                ->leftJoin('functions as f', 'f.function_no', '=', 'd.function_id')
                ->leftJoin('pages as p', 'p.page_no', '=', 'd.page_id')
                ->leftJoin('items as i', 'i.item_no', '=', 'd.item_id');

            if (!empty($cond['keyword'])) {
                $keyword = Lib::asteriskSearch($cond['keyword']);
                $query->orWhere(function ($sub) use ($keyword) {
                    $sub->where('f.function_name', 'LIKE', "{$keyword}");
                    $sub->orWhere('p.page_name', 'LIKE', "{$keyword}");
                    $sub->orWhere('i.item_name', 'LIKE', "{$keyword}");
                    $sub->orWhere('d.candidate', 'LIKE', "{$keyword}");
                });
            }
            $query->where($where);
            $this->writeLog(__METHOD__, false);
            return $query->count();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return 0;
        }
    }
    /**
     * Get status list by page no from divisions
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page_id' => number
     * ]
     * @return object|null
     */
    public function getDivisionsByPageNo($cond = null, $isPaginate = false) {
        try {
            $this->writeLog(__METHOD__);

            $subWhere = [
                'p.page_no' => $cond['page_id'],
                'it.item_name' => $cond['item_name']
            ];

            $subQuery = $this->from("pages as p")
                ->select(
                    'p.page_no',
                    'p.function_no',
                    'it.item_no'
                )
                ->leftJoin('items as it', 'p.page_no', '=', 'it.page_no')
                ->where($subWhere);
            $query = $this->fromSub($subQuery, 'sub')
                ->select(
                    'd.division_id',
                    'd.function_id',
                    'd.page_id',
                    'd.item_id',
                    'd.candidate',
                    'd.add_datetime',
                    'd.upd_datetime',
                    'd.add_user_id',
                    'd.upd_user_id',
                )
                ->leftJoin('divisions as d', function ($join) {
                    $join->on('d.page_id', '=', 'sub.page_no');
                    $join->on('d.function_id', '=', 'sub.function_no');
                    $join->on('d.item_id', '=', 'sub.item_no');
                });
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
     * Insert division
     *
     * @param object $divisionE   @var $fillable
     * @return object
     */
    public function insertData($divisionE) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $obj = $this->create($divisionE);
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
     * Update division
     *
     * @param object $divisionE
     * @param string $id
     * @return object
     */
    public function updateData($divisionE) {
        try {
            $this->writeLog(__METHOD__);
            if(!$divisionE) {
                return;
            }
            DB::beginTransaction();
            $where = [
                [$this->primaryKey, $divisionE[$this->primaryKey]]
            ];
            $obj = $this->where($where)->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $obj->update($divisionE);
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
     * Delete division by Id
     *
     * @param string $id
     * @return object
     */
    public function deleteData($id = null) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $where = [
                ['division_id', $id]
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
     * get division by division_id
     *
     * @param int|string $division_id
     * @param boolean $includeDeleted    true: get exists record, false: get only record is_deleted = 0, default: false
     *
     * @return null|object
     */
    public function getById($division_id = null, $includeDeleted = false)
    {
        try {
            $this->writeLog(__METHOD__);
            if (!$division_id) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $query = $this->from("{$this->table} as d")->select(
                'd.division_id',
                'd.function_id',
                'd.page_id',
                'd.item_id',
                'd.candidate',

                /**
                 * Extra info
                 */
                DB::raw("CONCAT(f.function_name, '/', p.page_name, '/', i.item_name) as division_name")
            )->where('division_id', $division_id)
            ->leftJoin('functions as f', 'f.function_no', '=', 'd.function_id')
            ->leftJoin('pages as p', 'p.page_no', '=', 'd.page_id')
            ->leftJoin('items as i', 'i.item_no', '=', 'd.item_id');
            // if (!$includeDeleted) {
            //     $query->where('is_deleted', DELETED_STATUS['NOT_DELETED']);
            // }
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
     * Get filter column
     * @param string $columnName column name
     * @return object|null
     * @date 2024/07/15
     * @author hung.le
     */
    public function getFilterColumn($columnName) {
        try {
            $this->writeLog(__METHOD__);
            if (empty($columnName)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param'), 3);
                return null;
            }
            $where = ['d.is_deleted' => DELETED_STATUS['NOT_DELETED']];
            $query = $this->from("{$this->table} as d")
                ->select($columnName)
                ->join('functions as f', 'f.function_no', '=', 'd.function_id')
                ->join('pages as p', 'p.page_no', '=', 'd.page_id')
                ->join('items as i', 'i.item_no', '=', 'd.item_id')
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
     * Get division list filter search
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     * @date 2024/07/23
     * @author hung.le
     */
    public function getListFilterSearch($cond = null, $isPaginate = true) {
        try {
            $this->writeLog(__METHOD__);
            $columnFilter = [
                'function_name',
                'page_name',
                'item_name',
                'candidate'
            ];
            //The page number for pagination
            $page = !empty($cond['page']) ? $cond['page'] : 0;
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $sort = !empty($cond['sort']) ? $cond["sort"] : null;
            $filter = !empty($cond['filter']) ? $cond["filter"] : null;
            $search = !empty($cond['search']) ? $cond["search"] : null;

            $where = [
                ['d.is_deleted', '=', DELETED_STATUS['NOT_DELETED']],
            ];
            // Query
            $query = $this->from("{$this->table} as d")
            ->select([
                'd.division_id',
                'd.function_id',
                'd.page_id',
                'd.item_id',
                'd.candidate',
                'd.upd_datetime'
            ])->leftJoin('functions as f', 'f.function_no', '=', 'd.function_id')
              ->leftJoin('pages as p', 'p.page_no', '=', 'd.page_id')
              ->leftJoin('items as i', 'i.item_no', '=', 'd.item_id')
              ->where($where);
            // Sort
            if (!empty($sort)) {
                if (Lib::checkValueExistInArray($columnFilter, $sort['column']) && Lib::checkValueExistInArray(ORDER_DIRECTION, strtolower($sort['direction']))) {
                    $query->orderBy($sort['column'], $sort['direction']);
                }
            }
            // Filter
            if (!empty($filter)) {
                foreach ($filter as $key => $value) {
                    if (Lib::checkValueExistInArray($columnFilter, $key)) {
                        if (is_array($value) && count($value) > 0) {
                            switch ($key) {
                                case 'function_name':
                                    $query->whereNotIn('f.function_name', $value);
                                    break;
                                case 'page_name':
                                    $query->whereNotIn('p.page_name', $value);
                                    break;
                                case 'item_name':
                                    $query->whereNotIn('i.item_name', $value);
                                    break;
                                case 'candidate':
                                    $query->whereNotIn('d.candidate', $value);
                                    break;
                            }
                        }
                    }
                }
            }
            // Text search filter
            if (!empty($search)) {
                foreach ($search as $key => $value) {
                    if (Lib::checkValueExistInArray($columnFilter, $key)) {
                        if (!Lib::isBlank($value)) {
                            $keyword = Lib::asteriskSearch($value);
                            switch ($key) {
                                case 'function_name':
                                    $query->where('f.function_name', 'LIKE', "{$keyword}");
                                    break;
                                case 'page_name':
                                    $query->where('p.page_name', 'LIKE', "{$keyword}");
                                    break;
                                case 'item_name':
                                    $query->where('i.item_name', 'LIKE', "{$keyword}");
                                    break;
                                case 'candidate':
                                    $query->where('d.candidate', 'LIKE', "{$keyword}");
                                    break;
                            }
                        }
                    }
                }
            }
            // Pagination
            if($isPaginate) {
                $page = isset($cond['page']) ? $cond['page'] : 0;
                $query->offset($page * PAGINATE_LIMIT)
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
     * Get division list filter search size total
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     * @date 2024/07/23
     * @author hung.le
     */
    public function getListFilterSearchSize($cond = null, $isPaginate = true) {
        try {
            $this->writeLog(__METHOD__);
            $columnFilter = [
                'function_name',
                'page_name',
                'item_name',
                'candidate'
            ];
            //The page number for pagination
            $page = !empty($cond['page']) ? $cond['page'] : 0;
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $sort = !empty($cond['sort']) ? $cond["sort"] : null;
            $filter = !empty($cond['filter']) ? $cond["filter"] : null;
            $search = !empty($cond['search']) ? $cond["search"] : null;

            $where = [
                ['d.is_deleted', '=', DELETED_STATUS['NOT_DELETED']],
            ];
            // Query
            $query = $this->from("{$this->table} as d")
              ->selectRaw('count(d.*) as count')
              ->leftJoin('functions as f', 'f.function_no', '=', 'd.function_id')
              ->leftJoin('pages as p', 'p.page_no', '=', 'd.page_id')
              ->leftJoin('items as i', 'i.item_no', '=', 'd.item_id')
              ->where($where);
            // Sort
            if (!empty($sort)) {
                if (Lib::checkValueExistInArray($columnFilter, $sort['column']) && Lib::checkValueExistInArray(ORDER_DIRECTION, strtolower($sort['direction']))) {
                    $query->orderBy($sort['column'], $sort['direction']);
                }
            }
            // Filter
            if (!empty($filter)) {
                foreach ($filter as $key => $value) {
                    if (Lib::checkValueExistInArray($columnFilter, $key)) {
                        if (is_array($value) && count($value) > 0) {
                            switch ($key) {
                                case 'function_name':
                                    $query->whereNotIn('f.function_name', $value);
                                    break;
                                case 'page_name':
                                    $query->whereNotIn('p.page_name', $value);
                                    break;
                                case 'item_name':
                                    $query->whereNotIn('i.item_name', $value);
                                    break;
                                case 'candidate':
                                    $query->whereNotIn('d.candidate', $value);
                                    break;
                            }
                        }
                    }
                }
            }
            // Text search filter
            if (!empty($search)) {
                foreach ($search as $key => $value) {
                    if (Lib::checkValueExistInArray($columnFilter, $key)) {
                        if (!Lib::isBlank($value)) {
                            $keyword = Lib::asteriskSearch($value);
                            switch ($key) {
                                case 'function_name':
                                    $query->where('f.function_name', 'LIKE', "{$keyword}");
                                    break;
                                case 'page_name':
                                    $query->where('p.page_name', 'LIKE', "{$keyword}");
                                    break;
                                case 'item_name':
                                    $query->where('i.item_name', 'LIKE', "{$keyword}");
                                    break;
                                case 'candidate':
                                    $query->where('d.candidate', 'LIKE', "{$keyword}");
                                    break;
                            }
                        }
                    }
                }
            }
            //
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query->count();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return 0;
        }
    }

    /**
     * Get division list dropdown
     *
     * @param array $cond [
     *  'function_name' => string     function name,
     *  'page_name' => string     page name,
     *  'item_name' => string     item name,
     *  'page' => int       page,
     *  'keyword' => string       keyword search,
     *  'alias_column'  =>  array       alias column `candidate`,
     * ]
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @return object|null
     *
     * @date 2024/07/23
     * @author duy.pham
     */
    public function getDivisionsDropdown($cond = null, $isPaginate = null) {
        try {
            $this->writeLog(__METHOD__);

            $functionName = !empty($cond["function_name"]) ? $cond["function_name"] : null;
            $pageName = !empty($cond["page_name"]) ? $cond["page_name"] : null;
            $itemName = !empty($cond["item_name"]) ? $cond["item_name"] : null;
            $page = !empty($cond['page']) ? $cond['page'] : 0;
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';
            $aliasName = !empty($cond['alias_column']) ? $cond['alias_column'] : 'candidate';

            $query = $this->from("{$this->table} as div")
                ->select(
                    'div.division_id',
                )
                ->selectRaw("div.candidate as {$aliasName}")
                ->leftJoin("functions as f", 'f.function_no', '=', 'div.function_id')
                ->leftJoin("pages as p", 'p.page_no', '=', 'div.page_id')
                ->leftJoin("items as i", 'i.item_no', '=', 'div.item_id')
                ->where('f.function_name', '=', $functionName)
                ->where('p.page_name', '=', $pageName)
                ->where('i.item_name', '=', $itemName);
            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $query->where('div.candidate', 'LIKE', $keyword);
            }

            if ($isPaginate) {
                $query->offset($page * PAGINATE_LIMIT)->limit(PAGINATE_LIMIT);
            }
            $results = $query->get();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $results;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Get total division list dropdown
     *
     * @param array $cond [
     *  'function_name' => string     function name,
     *  'page_name' => string     page name,
     *  'item_name' => string     item name,
     *  'keyword' => string       keyword search,
     * ]
     * @return object|null
     *
     * @date 2024/07/23
     * @author duy.pham
     */
    public function getTotalDivisionsDropdown($cond = null) {
        try {
            $this->writeLog(__METHOD__);

            $functionName = !empty($cond["function_name"]) ? $cond["function_name"] : null;
            $pageName = !empty($cond["page_name"]) ? $cond["page_name"] : null;
            $itemName = !empty($cond["item_name"]) ? $cond["item_name"] : null;
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';

            $query = $this->from("{$this->table} as div")
                ->leftJoin("functions as f", 'f.function_no', '=', 'div.function_id')
                ->leftJoin("pages as p", 'p.page_no', '=', 'div.page_id')
                ->leftJoin("items as i", 'i.item_no', '=', 'div.item_id')
                ->where('f.function_name', '=', $functionName)
                ->where('p.page_name', '=', $pageName)
                ->where('i.item_name', '=', $itemName);
            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $query->where('div.candidate', 'LIKE', $keyword);
            }
            $results = $query->count();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $results;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * Get division_id
     *
     * @param array $cond [
     *  'function_name' => string     function name,
     *  'page_name' => string     page name,
     *  'item_name' => string     item name,
     *  'candidate' => string       candidate
     * ]
     * @return object|null
     *
     * @date 2024/07/24
     * @author duy.pham
     */
    public function getDivisionId($cond = null) {
        try {
            $this->writeLog(__METHOD__);

            if (empty($cond["function_name"]) || empty($cond["page_name"]) || empty($cond["item_name"]) || empty($cond["candidate"])) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }

            $functionName = $cond["function_name"];
            $pageName = $cond["page_name"];
            $itemName = $cond["item_name"];
            $candidate = $cond["candidate"];

            $query = $this->from("{$this->table} as div")
                ->select(
                    'div.division_id',
                )
                ->leftJoin("functions as f", 'f.function_no', '=', 'div.function_id')
                ->leftJoin("pages as p", 'p.page_no', '=', 'div.page_id')
                ->leftJoin("items as i", 'i.item_no', '=', 'div.item_id')
                ->where('f.function_name', '=', $functionName)
                ->where('p.page_name', '=', $pageName)
                ->where('i.item_name', '=', $itemName)
                ->where('div.candidate', '=', $candidate);

            $results = $query->first();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $results;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }
}
