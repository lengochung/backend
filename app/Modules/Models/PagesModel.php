<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;
use App\Utils\Lib;

class PagesModel extends BaseModel {
    protected $table = 'pages';
    protected $primaryKey = 'page_no';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'page_no',
        'function_no',
        'page_name',
        'slug_name'
    ];

    /**
     * Get all function list
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
            $query = $this->from("{$this->table} as p")
            ->select(
                'p.page_no',
                'p.function_no',
                'p.page_name',
                'p.slug_name'
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
     * Get all function list
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

            $where = [];
            $query = $this->from("{$this->table} as p")
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
     * Get page by page_no
     * @param number $page_no
     * @return object|null
     * @author hung.le
     * @date 2024/07/24
     */
    public function getById($page_no = null) {
        try {
            $this->writeLog(__METHOD__);
            $where = [ $this->primaryKey => $page_no ];
            $obj = $this->from($this->table)
                ->select(
                    'page_no',
                    'page_name'
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
}
