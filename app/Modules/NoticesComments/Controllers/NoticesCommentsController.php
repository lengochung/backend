<?php
namespace App\Modules\NoticesComments\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Models\NoticesCommentsModel;
use App\Modules\Models\UserModel;
use App\Modules\NoticesComments\Requests\NoticesCommentsRequest;
use App\Utils\Files;
use Illuminate\Support\Facades\Validator;

class NoticesCommentsController extends BaseController {
    /**
     * The NoticesCommentsModel instance.
     */
    protected $noticesCommentsModel;

    /**
     * Create a new NoticesCommentsController instance.
     *
     * @return void
     */
    public function __construct(
        NoticesCommentsModel $noticesCommentsModel
    ) {
        parent::__construct ();
        $this->noticesCommentsModel = $noticesCommentsModel;
    }

    /**
     * Check validation before save notice-user
     *
     * @param  \Illuminate\Http\Request     $request
     *
     * @return \Illuminate\Support\MessageBag|null
     */
    private function checkValidationNoticesComment($request)
    {
        $currentRequest = new NoticesCommentsRequest();
        $validator = Validator::make($request->all(), $currentRequest->groupRules(), $currentRequest->groupMessages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return;
    }

    /**
     * Get all notices comments
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllList(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $totalRow = $this->noticesCommentsModel->getAllByNoticeNoSize($request->all());
            if (empty($totalRow)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }
            $result = $this->noticesCommentsModel->getAllByNoticeNo($request->all(), true);
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $result, $totalRow);
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
     */
    public function getDetail(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            if (empty($request->topic_no)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.group')));
            }
            if (empty($request->index)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.group')));
            }
            $params = [
                'notice_no' => $request->notice_no,
                'index' => $request->index
            ];
            $data = $this->noticesCommentsModel->getById($params);

            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $data);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Save notice-user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onSave(Request $request) {
        try {
            $this->writeLog(__METHOD__);

            //Check validation before save
            $checkValidation = $this->checkValidationNoticesComment($request);
            if (!is_null($checkValidation)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.invalid_validator'));
                return Lib::returnJsonResult(false, $checkValidation);
            }

            $inputRequest = [
                'notice_no' => $request->notice_no,
                'index' => 0,
                'post_message' => $request->post_message,
                'post_user_id' => $request->post_user_id,
                'post_datetime' => NOW(),
                'good' => !$request->good ? 0 : $request->good,
                'like' => !$request->like ? 0 : $request->like,
                'smile' => !$request->smile ? 0 : $request->smile,
                'surprise' => !$request->surprise ? 0 : $request->surprise,
            ];

            //Build data
            $input = Lib::assignData($this->noticesCommentsModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            /**
             * get max index comment of notice
             */
            $index = $this->noticesCommentsModel->getMaxIndex($input['notice_no']);
            if(!$index || $index == 0) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $input['index'] = $index;

            /**
             * Create new record
             */
            $result = $this->noticesCommentsModel->insertData($input);
            if(empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.create_failed'), json_encode($input)), 3);
                return Lib::returnJsonResult(false, __('message.create_failed'));
            }
            /**
             * Move files uploads pending submit
             */
            if($request->synchedFilesData) {
                /**
                 * Build path folder
                 */
                $folderPath = UserModel::getUserLogin()['office_id'] . DIRECTORY_SEPARATOR . "notice-comments" . DIRECTORY_SEPARATOR . $result->notice_no . DIRECTORY_SEPARATOR . $result->index;
                $isSynchedFiles = Files::synchedFiles($request->synchedFilesData, $folderPath);
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $result);

        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }


    /**
     * Update notice-comment message/file attached
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onUpdate(Request $request) {
        try {
            $this->writeLog(__METHOD__);

            //Check validation before save
            $checkValidation = $this->checkValidationNoticesComment($request);
            if (!is_null($checkValidation)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.invalid_validator'));
                return Lib::returnJsonResult(false, $checkValidation);
            }

            $inputRequest = [
                'notice_no' => $request->notice_no,
                'index' => $request->index,
                'post_message' => $request->post_message
            ];

            //Build data
            $input = Lib::assignData($this->noticesCommentsModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            /**
             * update record
             */
            $result = $this->noticesCommentsModel->updateData($input);
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.create_failed'), json_encode($input)), 3);
                return Lib::returnJsonResult(false, __('message.create_failed'));
            }
            /**
             * Move files uploads pending submit
             */
            if($request->synchedFilesData) {
                $isSynchedFiles = Files::synchedFiles($request->synchedFilesData);
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $result);

        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }
    /**
     * Reaction notice-comment
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onReaction(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            //Check validation before save
            $checkValidation = $this->checkValidationNoticesComment($request);
            if (!is_null($checkValidation)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.invalid_validator'));
                return Lib::returnJsonResult(false, $checkValidation);
            }
            $typeReaction = $request->typeReaction;
            if (!$typeReaction) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.invalid_validator'));
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $inputRequest = [
                'notice_no' => $request->notice_no,
                'index' => $request->index,
            ];
            //Build data
            $input = Lib::assignData($this->noticesCommentsModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'));
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $input['typeReaction'] = $typeReaction;
            /**
             * update reaction for comement
             */
            $result = $this->noticesCommentsModel->updateReactionData($input);
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)));
                return Lib::returnJsonResult(false, __('message.update_failed'));
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $result);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Delete by notice_no and index
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onDelete(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $notice_no = $request->notice_no;
            $index = $request->index;
            $updDatetime = $request->upd_datetime;
            if (!$notice_no) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_group_id'), 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (!$index) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' upd_datetime', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            //check multiple user update
            $where = [
                'notice_no' => $notice_no,
                'index' => $index,
            ];
            $errorMessage = $this->getMessageErrorMultiEdit($this->noticesCommentsModel->tablePublic, $where, $updDatetime, __('label.group'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            $result = $this->noticesCommentsModel->deleteData($request->all());
            if (!$result) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.delete_failed'), ($notice_no + '|' + $index), 3));
                return Lib::returnJsonResult(false, __('message.delete_failed'));
            }
            return Lib::returnJsonResult(true, "", $result);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Upload File
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @author hung.le 2024/07/17
     */
    public function onUpload(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $file = Files::getFileFromRequest($request->all());
            if($file) {
                $result = Files::onFileUpload($file);
                if(!$result) {
                    $this->writeLog(__METHOD__, false);
                    return Lib::returnJsonResult(false, '');
                }
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $result);
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(false, '');
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }
    /**
     * Delete File
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @author hung.le 2024/07/17
     */
    public function onDeleteFile(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $file = Files::getFileFromRequest($request->all());
            if($file) {
                $result = Files::onDelete($file);
                if(!$result) {
                    $this->writeLog(__METHOD__, false);
                    return Lib::returnJsonResult(false, '');
                }
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $result);
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(false, '');
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }
    /**
     * Get Files
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @author hung.le 2024/07/17
     */
    public function getFiles(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            if(!empty($request->folderPath)) {
                $resultFiles = Files::getFiles($request->all());
                if(!$resultFiles) {
                    $this->writeLog(__METHOD__, false);
                    return Lib::returnJsonResult(false, '');
                }

                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $resultFiles, count($resultFiles));
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(false, '');
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }
    /**
     * Get All Files from folder notice_no
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilesByNoticeNo(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            if(!empty($request->folderPath)) {
                $resultFiles = Files::getFiles($request->all());
                if(!$resultFiles) {
                    $this->writeLog(__METHOD__, false);
                    return Lib::returnJsonResult(false, '');
                }

                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $resultFiles, count($resultFiles));
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(false, '');
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

}
