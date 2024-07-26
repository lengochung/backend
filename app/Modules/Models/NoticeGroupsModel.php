<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;

/**
 * hung.le
 * 03.07.2024
 */
class NoticeGroupsModel extends BaseModel {
    protected $table = 'notice-groups';
    protected $primaryKey = ['notice_no', 'group_id'];
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'notice_no',
        'group_id',
    ];

    /**
     * Get all notice-groups list
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
            $query = $this->from("{$this->table} as ng")
            ->select(
                'ng.notice_no',
                'ng.group_id',
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
     * Get notice-group by id
     *
     * @param object $noticeGroupE
     * @return object|null
     */
    public function getById($noticeGroupE = null) {
        try {
            $this->writeLog(__METHOD__);
            /**
             * Condition
             */
            $where = [
                'topic_no' => $noticeGroupE['topic_no'],
                'group_id' => $noticeGroupE['group_id']
            ];
            /**
             *
             */
            $obj = $this->from("{$this->table} as ng")
            ->select(
                'ng.notice_no',
                'ng.group_id',
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
     * Insert notice-group
     *
     * @param object $noticeGroupE   @var $fillable
     * @return object
     */
    public function insertData($noticeGroupE) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $obj = $this->create($noticeGroupE);
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
     * Update noticeUser
     *
     * @param object $noticeGroupE
     * @return object
     */
    public function updateData($noticeGroupE) {
        try {
            $this->writeLog(__METHOD__);
            if(!$noticeGroupE) {
                return;
            }
            DB::beginTransaction();
            /**
             * Condition
             */
            $where = [
                'topic_no' => $noticeGroupE['topic_no'],
                'group_id' => $noticeGroupE['group_id']
            ];
            $obj = $this
            -> where($where)
            -> first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $obj->update($noticeGroupE);
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
     * Delete notice user by Id
     *
     * @param string $noticeGroupE
     * @return object
     */
    public function deleteData($noticeGroupE = null) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            /**
             * Condition
             */
            $where = [
                'topic_no' => $noticeGroupE['topic_no'],
                'group_id' => $noticeGroupE['group_id']
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
     * Upsert batch multi record
     * @param array|object $upsertData
     * @return int $affectRow
     */
    public function upsertData($upsertData = null, $isArray = false) {
        try {
            $this->writeLog(__METHOD__);

            $data = $this->assignData($upsertData, $isArray);
            $affectedRows = $this->upsert(
                $data,
                ['notice_no', 'group_id'], // Các cột duy nhất để xác định bản ghi
                [
                    'upd_datetime',
                    'upd_user_id',
                    'is_deleted'
                ] // Các cột cần cập nhật (nếu bản ghi đã tồn tại)
            );

            /**
             * Số dòng bị ảnh hưởng
             */
            if($affectedRows >= 0) {
                return $affectedRows;
            }

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return null;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }
}
