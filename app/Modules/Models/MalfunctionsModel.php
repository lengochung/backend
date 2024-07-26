<?php

namespace App\Modules\Models;

use Exception;
use App\Utils\Lib;
use App\Traits\HasCompositePrimaryKey;
use Illuminate\Support\Facades\DB;
use App\Modules\Common\Models\BaseModel;

class MalfunctionsModel extends BaseModel {
    use HasCompositePrimaryKey;
    protected $table = 'malfunctions';
    protected $primaryKey = ['malfunction_no', 'edition_no'];
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'malfunction_no',
        'edition_no',
        'office_id',
        'status',
        'notice_no',
        'subject',
        'incident_datetime',
        'building_id',
        'fuel_type',
        'facility_id',
        'facility_detail1',
        'failicty_detail2',
        'severity_level',
        'find_user',
        'detail',
        'impact',
        'attached_file',
        'close_date',
        'is_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'incident_datetime' => 'date:'.DATE_FORMAT,
        'close_date' => 'date:'.DATE_FORMAT,
    ];

    /**
     * Get malfunction dropdown
     *
     * @param array $cond [
     *  'keyword' => string     keyword search
     *  'page' => int           page
     * ]
     * @param boolean $isPaginate    true: get paginate, false: get all
     * @return object|null
     *
     * @date 2024/07/25
     * @author duy.pham
     */
    public function getMalfunctionDropdown($cond = null, $isPaginate = null) {
        try {
            $this->writeLog(__METHOD__);

            $page = !empty($cond['page']) ? $cond['page'] : 0;
            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';

            $where = [
                ['m.office_id', '=', $this->getUserProperty('office_id')],
            ];

            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchByKeyword = function ($query) use ($keyword) {
                    $query->where('m.malfunction_no', 'LIKE', $keyword);
                    $query->orWhere('m.subject', 'LIKE', $keyword);
                };
                array_push($where, [$searchByKeyword]);
            }

            $query = $this->from("{$this->table} as m")
                ->select(
                    'm.malfunction_no',
                    'm.subject',
                )
                ->where($where)
                ->groupBy('m.malfunction_no', 'm.subject')
                ->orderBy('m.malfunction_no', 'DESC');

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
     * Get total malfunction dropdown
     *
     * @param array $cond [
     *  'keyword' => string       keyword search
     * ]
     * @return object|null
     *
     * @date 2024/07/25
     * @author duy.pham
     */
    public function getTotalMalfunctionDropdown($cond = null) {
        try {
            $this->writeLog(__METHOD__);

            $keyword = !empty($cond['keyword']) ? $cond['keyword'] : '';

            $where = [
                ['m.office_id', '=', $this->getUserProperty('office_id')],
            ];

            if (!empty($keyword)) {
                $keyword = Lib::asteriskSearch($keyword);
                $searchByKeyword = function ($query) use ($keyword) {
                    $query->where('m.malfunction_no', 'LIKE', $keyword);
                    $query->orWhere('m.subject', 'LIKE', $keyword);
                };
                array_push($where, [$searchByKeyword]);
            }

            $query = $this->from("{$this->table} as m")
                ->select(
                    'm.malfunction_no',
                    'm.subject'
                )
                ->where($where)
                ->groupBy('m.malfunction_no', 'm.subject');

            $count = DB::table( DB::raw("({$query->toSql()}) as sub") )
                ->mergeBindings($query->getQuery())
                ->count();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $count;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }
}
