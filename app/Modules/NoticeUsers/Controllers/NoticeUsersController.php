<?php
namespace App\Modules\NoticeUsers\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Models\NoticeUsersModel;
use App\Modules\NoticeUsers\Requests\NoticeUsersRequest;
use Illuminate\Support\Facades\Validator;

class NoticeUsersController extends BaseController {
    /**
     * The NoticeUsersModel instance.
     */
    protected $noticeUsersModel;

    /**
     * Create a new NoticeUsersController instance.
     *
     * @return void
     */
    public function __construct(
        NoticeUsersModel $noticeUsersModel
    ) {
        parent::__construct ();
        $this->noticeUsersModel = $noticeUsersModel;
    }

    /**
     * Check validation before save notice-user
     *
     * @param  \Illuminate\Http\Request     $request
     *
     * @return \Illuminate\Support\MessageBag|null
     * @date 2024/07/05
     * @author hung.le
     */
    private function checkValidationDivision($request)
    {
        $currentRequest = new NoticeUsersRequest();
        $validator = Validator::make($request->all(), $currentRequest->groupRules(), $currentRequest->groupMessages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return;
    }

    /**
     * Get all noticeUsers
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
     */
    public function getAllList(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $result = $this->noticeUsersModel->getAllList($request->all());
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            $response = $result;
            $totalRow = $result->count();
            $this->writeLog(__METHOD__, false);

            return Lib::returnJsonResult(true, '', $response, $totalRow);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }
    /**
     * Get notice-user detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
     */
    public function getDetail(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            if (empty($request->topic_no)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.group')));
            }
            if (empty($request->user_id)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.group')));
            }
            $params = [
                'topic_no' => $request->topic_no,
                'user_id' => $request->user_id
            ];
            $data = $this->noticeUsersModel->getById($params);

            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $data);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Save notice-user
     * @date 2024/07/05
     * @author hung.le
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onSave(Request $request) {
        try {
            $this->writeLog(__METHOD__);

            //Check validation before save
            $checkValidation = $this->checkValidationDivision($request);
            if (!is_null($checkValidation)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.invalid_validator'));
                return Lib::returnJsonResult(false, $checkValidation);
            }

            $inputRequest = [
                // 'notice-user_id' => $request->notice-user_id,
                // 'function_id' => $request->function_id,
                // 'page_id' => $request->page_id,
                // 'item_id' => $request->item_id,
                // 'candidate' => $request->candidate
            ];

            //Build data
            $input = Lib::assignData($this->noticeUsersModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            /**
             * Lưu mới hoặc chỉnh sửa
             */
            $isNew = $request->is_new ? true : false;

            if ($isNew) {
                unset($input["notice-user_no"]);
                $result = $this->noticeUsersModel->insertData($input);
                if (empty($result)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.create_failed'), json_encode($input)), 3);
                    return Lib::returnJsonResult(false, __('message.create_failed'));
                }
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $result);
            }

            //update group
            /**
             * Recheck notice-user_id after update
             */
            if(!isset($input["notice-user_no"]) || $input["notice-user_no"] <= 0) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)), 3);
                return Lib::returnJsonResult(false, __('message.update_failed'));
            }
            $result = $this->noticeUsersModel->updateData($input);
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)), 3);
                return Lib::returnJsonResult(false, __('message.update_failed'));
            }

            $this->writeLog(__METHOD__, false);

            return Lib::returnJsonResult(true, '', $result);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

}
