<?php

namespace App\Modules\Models;

use Exception;
use App\Modules\Common\Models\BaseModel;
use App\Utils\Lib;
use Illuminate\Support\Facades\DB;

class NoticesCommentsModel extends BaseModel {
    protected $table = 'notices-comments';
    public $tablePublic = 'notices-comments';
    protected $primaryKey = ['notice_no', 'index'];
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'notice_no',
        'index',
        'post_user_id',
        'post_datetime',
        'post_message',
        'attached_file',
        'good',
        'like',
        'smile',
        'surprise'
    ];

    /**
     * Get all notices-comments list
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     * @author
     * @date 2024/07/05
     * @author hung.le
     */
    public function getAllList($cond = null, $isPaginate = true) {
        try {
            $this->writeLog(__METHOD__);
            $where = [
                'notice_no' => $cond['notice_no']
            ];
            $query = $this->from("{$this->table} as nc")
            ->select(
                'nc.notice_no',
                'nc.index',
                'nc.post_user_id',
                'nc.post_datetime',
                'nc.post_message',
                'nc.attached_file',
                'nc.good',
                'nc.like',
                'nc.smile',
                'nc.surprise',
                'nc.upd_datetime',

                /**
                 * Extra field
                 */
                DB::raw("CONCAT(u.user_first_name, ' ', u.user_last_name) as post_user_fullname")
            )->leftJoin('users as u', 'nc.post_user_id', '=', 'u.user_id')
            ->where($where)
            ->orderBy('nc.post_datetime', $this->ASC);
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
     * Get size all notices-comments list
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number
     * ]
     * @return object|null
     * @date 2024/07/05
     * @author hung.le
     */
    public function getAllListSize($cond = null) {
        try {
            $this->writeLog(__METHOD__);
            $where = [
                'notice_no' => $cond['notice_no']
            ];
            $query = $this->from("{$this->table} as nc")
	            ->selectRaw('count(*) as count')
	            ->leftJoin('users as u', 'nc.post_user_id', '=', 'u.user_id')
	            ->where($where);

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query->count();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return 0;
        }
    }
    /**
     * Get all notices-comments list by notice_no
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number,
     *  'notice_no' => number
     * ]
     * @return object|null
     * @date 2024/07/05
     * @author hung.le
     */
    public function getAllByNoticeNo($cond = null, $isPaginate = true) {
        try {
            $this->writeLog(__METHOD__);
            $where = [
                'nc.notice_no' => $cond['notice_no']
            ];
            $query = $this->from("{$this->table} as nc")
            ->select(
                'nc.notice_no',
                'nc.index',
                'nc.post_user_id',
                'nc.post_datetime',
                'nc.post_message',
                'nc.attached_file',
                'nc.good',
                'nc.like',
                'nc.smile',
                'nc.surprise',
                'nc.upd_datetime',
                 /**
                 * Extra field
                 */
                DB::raw("CONCAT(u.user_first_name, ' ', u.user_last_name) as post_user_fullname")
            )->leftJoin('users as u', 'nc.post_user_id', '=', 'u.user_id')
            ->where($where)
            ->orderBy('nc.post_datetime', $this->ASC);

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
     * Get size notices-comments list by notice_no
     * @param boolean $isPaginate    true: get paginate, false: get all
     *
     * @param array $cond [
     *  'page' => number,
     *  'notice_no' => number
     * ]
     * @return object|null
     * @date 2024/07/05
     * @author hung.le
     */
    public function getAllByNoticeNoSize($cond = null, $isPaginate = true) {
        try {
            $this->writeLog(__METHOD__);
            $where = [
                'nc.notice_no' => $cond['notice_no']
            ];
            $query = $this->from("{$this->table} as nc")
            ->select(
                'nc.notice_no',
                'nc.index',
                'nc.post_user_id',
                'nc.post_datetime',
                'nc.post_message',
                'nc.attached_file',
                'nc.good',
                'nc.like',
                'nc.smile',
                'nc.surprise',
                'nc.upd_datetime',
                 /**
                 * Extra field
                 */
                DB::raw("CONCAT(u.user_first_name, ' ', u.user_last_name) as post_user_fullname")
            )->leftJoin('users as u', 'nc.post_user_id', '=', 'u.user_id')
            ->where($where);

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query->count();
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return 0;
        }
    }
    /**
     * Get notices-comments by id
     *
     * @param object $noticeCommentE
     * @return object|null
     * @date 2024/07/05
     * @author hung.le
     */
    public function getById($noticeCommentE = null) {
        try {
            $this->writeLog(__METHOD__);
            /**
             * Condition
             */
            $where = [
                'topic_no' => $noticeCommentE['topic_no'],
                'index' => $noticeCommentE['index']
            ];
            /**
             *
             */
            $obj = $this->from("{$this->table} as nc")
            ->select(
                'nc.notice_no',
                'nc.index',
                'nc.post_user_id',
                'nc.post_datetime',
                'nc.post_message',
                'nc.attached_file',
                'nc.good',
                'nc.like',
                'nc.smile',
                'nc.surprise'
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
     * Insert notices-comments
     *
     * @param object $noticeCommentE   @var $fillable
     * @return object
     * @date 2024/07/05
     * @author hung.le
     */
    public function insertData($noticeCommentE) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            $obj = $this->create($noticeCommentE);
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
     * Update notices-comments post-message/file attached
     *
     * @param object $noticeCommentE
     * @return object
     * @date 2024/07/05
     * @author hung.le
     */
    public function updateData($noticeCommentE) {
        try {
            $this->writeLog(__METHOD__);
            if(!$noticeCommentE) {
                return;
            }
            DB::beginTransaction();
            /**
             * Condition
             */
            $where = [
                'notice_no' => $noticeCommentE['notice_no'],
                'index' => $noticeCommentE['index']
            ];
            $obj = $this->where($where)->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $input = $this->assignData($noticeCommentE);
            $this->where($where)->update($input);
            $obj = $this->where($where)->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
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
     * Update reaction notices-comments
     * @author hung.le 2024/07/18
     * @param object $noticeCommentE
     * @return object
     */
    public function updateReactionData($noticeCommentE) {
        try {
            $this->writeLog(__METHOD__);
            if(!$noticeCommentE) {
                return;
            }
            DB::beginTransaction();
            $where = [
                'notice_no' => $noticeCommentE['notice_no'],
                'index' => $noticeCommentE['index']
            ];
            $obj = $this->where($where)->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $noticeCommentE[$noticeCommentE['typeReaction']] = $obj[$noticeCommentE['typeReaction']] + 1;
            unset($noticeCommentE['typeReaction']);
            $input = $this->assignData($noticeCommentE, false, true);
            $this->where($where)->update($input);

            $obj = $this->where($where)->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
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
     * Delete notices-comments by Id
     *
     * @param string $noticeCommentE
     * @return object
     * @date 2024/07/05
     * @author hung.le
     */
    public function deleteData($noticeCommentE = null) {
        try {
            $this->writeLog(__METHOD__);
            DB::beginTransaction();
            /**
             * Condition
             */
            $where = [
                'notice_no' => $noticeCommentE['notice_no'],
                'index' => $noticeCommentE['index']
            ];
            $query = $this
            -> where($where);
            $obj = $query->first();
            if (empty($obj)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return;
            }
            $result = $query->delete();

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
     * Get max index of notice
     *
     * @param number $notice_no
     * @return object
     * @date 2024/07/05
     * @author hung.le
     */
    public function getMaxIndex($notice_no = null) {
        try {
            $this->writeLog(__METHOD__);
            $where = [
                'notice_no' => $notice_no
            ];
            $maxValue = $this->where($where)->max('index');
            if (empty($maxValue)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return 1;
            };
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $maxValue + 1;
        } catch (Exception $e) {
            DB::rollback();
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return null;
        }
    }
}
