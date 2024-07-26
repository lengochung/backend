<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;

class OfficeModel extends BaseModel
{
    protected $table = 'offices';
    protected $primaryKey = 'office_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'office_id',
        'group_office_id',
        'office_name',
        'office_subname',
        'zip',
        'address',
        'tel',
        'fax',
        'expiry_date',
        'is_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'expiry_date' => 'date:'.DATE_FORMAT,
    ];

    /**
     * get office by office id
     *
     * @param int $id    office id
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
            $query = $this
                ->where('office_id', $id);
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
     * Get all office list
     *
     * @param array $cond [
     *  'group_office_id' => int
     * ]
     * @return object|null
     */
    public function getAllList($cond = null)
    {
        try {
            $this->writeLog(__METHOD__);

            $groupOfficeId = !empty($cond['group_office_id']) ? $cond['group_office_id'] : 0;

            $where = [];
            if ($groupOfficeId) {
                array_push($where, ['off.group_office_id', '=', $groupOfficeId]);
            }

            $result = $this->from("{$this->table} as off")
            ->select(
                'off.office_id',
                'off.group_office_id',
                'off.office_name',
                'off.office_subname',
            )
            ->where($where)
            ->get();
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $result;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }

}
