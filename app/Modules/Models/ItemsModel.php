<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;
use App\Utils\Lib;

class ItemsModel extends BaseModel {
    protected $table = 'items';
    protected $primaryKey = 'item_no';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_no',
        'page_no',
        'item_name',
    ];

    /**
     * Get all item list
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     */
    public function getAllList($cond = null, $isPaginate = false) {
        try {
            $this->writeLog(__METHOD__);

            $where = [];
            $query = $this->from("{$this->table} as i")
            ->select(
                'i.item_no',
                'i.page_no',
                'i.item_name'
            )->where($where);

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
     * Get item list size
     * @param array $cond
     * @return object|null
     * @author hung.le
     * @date 2024/07/24
     */
    public function getAllListSize($cond = null) {
        try {
            $this->writeLog(__METHOD__);

            $where = [];
            $query = $this->from("{$this->table} as i")
	            ->selectRaw('count(*) as count')
	            ->where($where);

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query->count();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return 0;
        }
    }
    /**
     * Get item by item_no
     * @param number $item_no
     * @return object|null
     * @author hung.le
     * @date 2024/07/24
     */
    public function getById($item_no = null) {
        try {
            $this->writeLog(__METHOD__);
            $where = [ $this->primaryKey => $item_no ];
            $obj = $this->from($this->table)
                ->select(
                    'item_no',
                    'item_name'
                )-> where($where)
                 -> first();
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
     * Get item list dropdown
     *
     * @param array $cond [
     *  'function_name' => string     function name,
     *  'page_name' => string     page name,
     *  'page' => int       page,
     *  'keyword' => string       keyword search
     * ]
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @return object|null
     *
     * @date 2024/07/24
     * @author duy.pham
     */
    public function getItemsDropdown($cond = null, $isPaginate = null) {
        try {
            $this->writeLog(__METHOD__);

            $functionName = !empty($cond["function_name"]) ? $cond["function_name"] : null;
            $pageName = !empty($cond["page_name"]) ? $cond["page_name"] : null;
            $page = !empty($cond['page']) ? $cond['page'] : 0;
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';

            $query = $this->from("{$this->table} as i")
                ->select(
                    'i.item_no',
                    'i.item_name',
                )
                ->leftJoin("pages as p", 'p.page_no', '=', 'i.page_no')
                ->leftJoin("functions as f", 'f.function_no', '=', 'p.function_no')
                ->where('f.function_name', '=', $functionName)
                ->where('p.page_name', '=', $pageName);
            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $query->where('i.item_name', 'LIKE', $keyword);
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
     * Get item list dropdown total
     *
     * @param array $cond [
     *  'function_name' => string     function name,
     *  'page_name' => string     page name,
     *  'keyword' => string       keyword search
     * ]
     * @return int|null
     *
     * @date 2024/07/24
     * @author duy.pham
     */
    public function getTotalItemsDropdown($cond = null) {
        try {
            $this->writeLog(__METHOD__);

            $functionName = !empty($cond["function_name"]) ? $cond["function_name"] : null;
            $pageName = !empty($cond["page_name"]) ? $cond["page_name"] : null;
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';

            $query = $this->from("{$this->table} as i")
                ->leftJoin("pages as p", 'p.page_no', '=', 'i.page_no')
                ->leftJoin("functions as f", 'f.function_no', '=', 'p.function_no')
                ->where('f.function_name', '=', $functionName)
                ->where('p.page_name', '=', $pageName);
            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $query->where('i.item_name', 'LIKE', $keyword);
            }

            $results = $query->count();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $results;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }
}
