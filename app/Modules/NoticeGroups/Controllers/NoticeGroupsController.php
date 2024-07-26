<?php
namespace App\Modules\NoticeGroups\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Models\NoticeGroupsModel;
use App\Modules\NoticesGroups\Requests\NoticeGroupsRequest;
use Illuminate\Support\Facades\Validator;

class NoticeGroupsController extends BaseController {
    /**
     * The NoticeGroupsModel instance.
     */
    protected $noticeGroupsModel;

    /**
     * Create a new NoticeGroupsController instance.
     *
     * @return void
     */
    public function __construct(
        NoticeGroupsModel $noticeGroupsModel
    ) {
        parent::__construct ();
        $this->noticeGroupsModel = $noticeGroupsModel;
    }

    /**
     * Check validation before save notice groups
     *
     * @param  \Illuminate\Http\Request     $request
     *
     * @return \Illuminate\Support\MessageBag|null
     * @date 2024/07/05
     * @author hung.le
     */
    private function checkValidationDivision($request)
    {
        $currentRequest = new NoticeGroupsRequest();
        $validator = Validator::make($request->all(), $currentRequest->groupRules(), $currentRequest->groupMessages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return;
    }

    /**
     * Get all notice groups
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
     */
    public function getAllList(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $result = $this->noticeGroupsModel->getAllList($request->all());
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $this->writeLog(__METHOD__, false);
            $totalRow = $result->count();
            return Lib::returnJsonResult(true, '', $result, $totalRow);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }
    /**
     * Get notice groups detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse\
     * @date 2024/07/05
     * @author hung.le
     */
    public function getDetail(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            if (empty($request->notice_no)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.group')));
            }
            if (empty($request->group_id)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.group')));
            }
            $params = [
                'notice_no' => $request->notice_no,
                'group_id' => $request->group_id
            ];
            $data = $this->noticeGroupsModel->getById($params);

            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $data);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Save notice groups
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
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
                // 'notice-group_id' => $request->notice-group_id,
                // 'function_id' => $request->function_id,
                // 'page_id' => $request->page_id,
                // 'item_id' => $request->item_id,
                // 'candidate' => $request->candidate
            ];

            //Build data
            $input = Lib::assignData($this->noticeGroupsModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            /**
             * Lưu mới hoặc chỉnh sửa
             */
            $isNew = $request->is_new ? true : false;

            if ($isNew) {
                $result = $this->noticeGroupsModel->insertData($input);
                if (empty($result)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.create_failed'), json_encode($input)), 3);
                    return Lib::returnJsonResult(false, __('message.create_failed'));
                }
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $result);
            }

            //update group
            /**
             * Recheck after update
             */
            if(!isset($input["notice_no"])) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)), 3);
                return Lib::returnJsonResult(false, __('message.update_failed'));
            }
            $result = $this->noticeGroupsModel->updateData($input);
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
