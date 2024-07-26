<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;
use App\Utils\Lib;

class FunctionsModel extends BaseModel {
    protected $table = 'functions';
    protected $primaryKey = 'function_no';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'function_no',
        'function_name'
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
            $query = $this->from("{$this->table} as f")
                ->select(
                    'f.function_no',
                    'f.function_name',
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
    public function getAllListSize($cond = null) {
        try {
            $this->writeLog(__METHOD__);

            $where = [];
            $query = $this->from("{$this->table} as f")
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
     * Get function by function_no
     * @param number $function_no
     * @return object|null
     * @author hung.le
     * @date 2024/07/24
     */
    public function getById($function_no = null) {
        try {
            $this->writeLog(__METHOD__);
            $where = [ $this->primaryKey => $function_no ];
            $obj = $this->from($this->table)
                ->select(
                    'function_no',
                    'function_name'
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
