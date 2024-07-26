<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;

class NoticeUsersModel extends BaseModel {
    protected $table = 'notice-users';
    protected $primaryKey = ['notice_no', 'user_id'];
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'notice_no',
        'user_id',
    ];

    /**
     * Get all notice-users list
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     * @date 2024/07/05
     * @author hung.le
     */
    public function getAllList($cond = null, $isPaginate = false) {
        try {
            $this->writeLog(__METHOD__);
            $where = [];
            $query = $this->from("{$this->table} as nu")
            ->select(
                'nu.notice_no',
                'nu.user_id',
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
     * Get notice-user by id
     *
     * @param object $noticeUserE
     * @return object|null
     * @date 2024/07/05
     * @author hung.le
     */
    public function getById($noticeUserE = null) {
        try {
            $this->writeLog(__METHOD__);
            /**
             * Condition
             */
            $where = [
                'topic_no' => $noticeUserE['topic_no'],
                'user_id' => $noticeUserE['user_id']
            ];
            /**
             *
             */
            $obj = $this->from("{$this->table} as nu")
            ->select(
                'nu.notice_no',
                'nu.user_id',
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
     * Insert notice-user
     *
     * @param object $noticeUserE   @var $fillable
     * @return object
     * @date 2024/07/05
     * @author hung.le
     */
    public function insertData($noticeUserE) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $obj = $this->create($noticeUserE);
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
     * @param object $noticeUserE
     * @return object
     * @date 2024/07/05
     * @author hung.le
     */
    public function updateData($noticeUserE) {
        try {
            $this->writeLog(__METHOD__);
            if(!$noticeUserE) {
                return;
            }
            DB::beginTransaction();
            /**
             * Condition
             */
            $where = [
                'topic_no' => $noticeUserE['topic_no'],
                'user_id' => $noticeUserE['user_id']
            ];
            $obj = $this
            -> where($where)
            -> first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $obj->update($noticeUserE);
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
     * @param string $noticeUserE
     * @return object
     * @date 2024/07/05
     * @author hung.le
     */
    public function deleteData($noticeUserE = null) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            /**
             * Condition
             */
            $where = [
                'topic_no' => $noticeUserE['topic_no'],
                'user_id' => $noticeUserE['user_id']
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
     * @date 2024/07/05
     * @author hung.le
     */
    public function upsertData($upsertData = null, $isArray = false) {
        try {
            $this->writeLog(__METHOD__);

            $data = $this->assignData($upsertData, $isArray);
            $affectedRows = $this->upsert(
                $data,
                ['notice_no', 'user_id'], // Các cột duy nhất để xác định bản ghi
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
