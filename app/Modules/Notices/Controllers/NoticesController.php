<?php
namespace App\Modules\Notices\Controllers;

use App\Components\SendMail;
use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Notices\Requests\NoticesRequest;
use App\Modules\Models\NoticesModel;
use App\Modules\Models\UserAndGroupModel;
use App\Modules\Models\UserModel;
use App\Utils\Files;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Validator;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

class NoticesController extends BaseController {
    /**
     * The NoticesModel instance.
     */
    protected $noticesModel;
    protected $sendMail;
    protected $userModel;

    /**
     * Create a new NoticesController instance.
     *
     * @return void
     */
    public function __construct(
        NoticesModel $noticesModel,
        SendMail $sendMail,
        UserModel $userModel,
    ) {
        parent::__construct ();
        $this->noticesModel = $noticesModel;
        $this->sendMail = $sendMail;
        $this->userModel = $userModel;
    }

    /**
     * Check validation before save notice
     *
     * @param  \Illuminate\Http\Request     $request
     *
     * @return \Illuminate\Support\MessageBag|null
     * @date 2024/07/05
     * @author hung.le
     */
    private function checkValidationNotice($request)
    {
        $currentRequest = new NoticesRequest();
        $validator = Validator::make($request->all(), $currentRequest->noticeRules(), $currentRequest->noticeMessages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return;
    }

    /**
     * Get all notices
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
     */
    public function getAllList(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $totalRow = $this->noticesModel->getAllListFromFilterBoxSize($request->all());
            if (empty($totalRow)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }
            $result = $this->noticesModel->getAllListFromFilterBox($request->all());
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
     * Get notice detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
     */
    public function getDetail(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $notice_no = $request->notice_no;
            if (empty($notice_no)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.group')));
            }
            $data = $this->noticesModel->getById($notice_no);

            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $data);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     *  Save notice by edit_type
     *  0: create
     *  1: save update
     *  2: save update synch and send email
     *  3: close notice
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @author hung.le 2024/07/05
     */
    public function onSave(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            /**
             * Create or update or synch or close
             */
            $edit_type = $request->edit_type;
            if (!(isset($edit_type) && $edit_type !== null && $edit_type !== '')) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            // //Check validation before save
            if($edit_type == 0 || $edit_type == 1 || $edit_type == 2) {
                $checkValidation = $this->checkValidationNotice($request);
                if (!is_null($checkValidation)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.invalid_validator'));
                    return Lib::returnJsonResult(false, $checkValidation);
                }
            }
            /**
             * Recipient user, group ids
             */
            $recipient_user_ids = []; // user_ids
            $recipient_group_ids = []; // group_ids
            if($edit_type == 0 || $edit_type == 1 || $edit_type == 2) {
                $recipient_ids = $request->recipient_ids;
                if(!empty($recipient_ids)) {
                    foreach($recipient_ids as $recipient) {
                        $array = explode(UserAndGroupModel::TYPE['delimiter'], $recipient);
                        if(count($array) != 2) continue;
                        if($array[1] == UserAndGroupModel::TYPE['user']) array_push($recipient_user_ids, $array[0]);
                        if($array[1] == UserAndGroupModel::TYPE['group']) array_push($recipient_group_ids, $array[0]);
                    }
                }
            }
            /**
             * Create new
             */
            if($edit_type == 0) {
                $inputRequest = [
                    'office_id' => $request->office_id,
                    'status_id' => $request->status_id,
                    'subject' => $request->subject,
                    'event_date' => $request->event_date,
                    'building_id' => $request->building_id,
                    'fuel_type' => $request->fuel_type,
                    'facility_id' => $request->facility_id,
                    'facility_detail1' => $request->facility_detail1,
                    'facility_detail2' => $request->facility_detail2,
                    'user_id' => $request->user_id,
                    'detail' => $request->detail,
                    'attached_file' => $request->attached_file,
                    // 'recipient_user_id' => $request->recipient_user_id,
                    // 'recipient_group_id' => $request->recipient_group_id
                ];
                //Build data
                $input = Lib::assignData($this->noticesModel->getFillable(), $inputRequest);
                if (!$input) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                    return Lib::returnJsonResult(false, __('message.err_unknown'));
                }
                unset($input["notice_no"]);
                if(!empty($recipient_user_ids)) $input['recipient_user_ids'] = $recipient_user_ids;
                if(!empty($recipient_group_ids)) $input['recipient_group_ids'] = $recipient_group_ids;
                $result = $this->noticesModel->insertData($input);
                if (empty($result)) {
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
                    $folderPath = UserModel::getUserLogin()['office_id'] . DIRECTORY_SEPARATOR . "notices" . DIRECTORY_SEPARATOR . $result->notice_no;
                    $isSynchedFiles = Files::synchedFiles($request->synchedFilesData, $folderPath);
                }

                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $result);
            }

            // Check multiple user update
            $where = [
                'notice_no' => $request->notice_no,
            ];
            $upd_datetime = $request->upd_datetime;
            $errorMessage = $this->getMessageErrorMultiEdit($this->noticesModel->tablePublic, $where, $upd_datetime, __('label.group'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            // update or synched notice
            if($edit_type == 1 || $edit_type == 2) {
                $inputRequest = [
                    'notice_no' => $request->notice_no,
                    'office_id' => $request->office_id,
                    'status_id' => $request->status_id,
                    'subject' => $request->subject,
                    'event_date' => $request->event_date,
                    'building_id' => $request->building_id,
                    'fuel_type' => $request->fuel_type,
                    'facility_id' => $request->facility_id,
                    'facility_detail1' => $request->facility_detail1,
                    'facility_detail2' => $request->facility_detail2,
                    'user_id' => $request->user_id,
                    'detail' => $request->detail,
                    'attached_file' => $request->attached_file,
                    // 'recipient_user_id' => $request->recipient_user_id,
                    // 'recipient_group_id' => $request->recipient_group_id,
                    'broadcast_user_id' => $request->broadcast_user_id,
                    'broadcast_datetime' => Lib::toMySqlNow(),
                    // 'close_user_id' => $request->close_user_id,
                    // 'close_datetime' => $request->close_datetime,
                ];
                //Build data
                $input = Lib::assignData($this->noticesModel->getFillable(), $inputRequest);
                if (!$input) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                    return Lib::returnJsonResult(false, __('message.err_unknown'));
                }
                if(!empty($recipient_user_ids)) $input['recipient_user_ids'] = $recipient_user_ids;
                if(!empty($recipient_group_ids)) $input['recipient_group_ids'] = $recipient_group_ids;
                $result = $this->noticesModel->updateData($input);
                if (empty($result)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)), 3);
                    return Lib::returnJsonResult(false, __('message.update_failed'));
                }
                /**
                 * Move/delete files uploads pending submit
                 */
                if($request->synchedFilesData) {
                    $isSynchedFiles = Files::synchedFiles($request->synchedFilesData);
                }
                /**
                 * Send email if edit_type is synched (click button  発信 )
                 */
                if($edit_type == 2) {
                    if(!empty($recipient_user_ids) || !empty($recipient_group_ids)) {
                        $listEmailUsers = [];
                        if(!empty($recipient_user_ids)) { // get users by user ids
                            $cond['user_ids'] = $recipient_user_ids;
                            $usersByUserIds = $this->userModel->getUserListByUserIds($cond);
                            if($usersByUserIds) {
                                foreach ($usersByUserIds as $user) {
                                    if($user->mail)
                                        array_push($listEmailUsers, $user->mail);
                                }
                            }
                        }
                        if(!empty($recipient_group_ids)) { // get users by group ids
                            $cond['group_ids'] = $recipient_group_ids;
                            $usersByGroupIds = $this->userModel->getUserListByGroupIds($cond);
                            if($usersByGroupIds) {
                                foreach ($usersByGroupIds as $user) {
                                    if ($user->mail && !in_array($user->mail, $listEmailUsers)) {
                                        array_push($listEmailUsers, $user->mail);
                                    }
                                }
                            }
                        }
                        // Send mail
                        $isSendMail = $this->sendMail->sendMail([
                            'subject' => $result->subject, //Subject email
                            // 'mail_to_name' => $listEmailUsers, //Email recipient's name
                            'mail_to' => $listEmailUsers,   //Email sent to (Ex array: ['m1@gmail.com', 'm2@gmail.com, ...'])
                            'from_name' => 'From facility', //Email from name
                            'mail_content' => $result->detail
                        ]);
                    }
                }

                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $result);
            }

            if($edit_type == 3) {
                return $this->onClose($request);
            }
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Close notice
     * edit_type = 3
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
     */
    private function onClose(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            //
            $inputRequest = [
                'notice_no' => $request->notice_no,
                'status_id' => $request->status_id,
                'close_user_id' => $request->close_user_id,
                'close_datetime' => Lib::toMySqlNow(),
            ];
            //Build data
            $input = Lib::assignData($this->noticesModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (empty($input['notice_no'])) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (empty($input['close_user_id'])) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            // Check multiple user update
            $where = [
                'notice_no' => $request->notice_no,
            ];
            $upd_datetime = $request->upd_datetime;
            $errorMessage = $this->getMessageErrorMultiEdit($this->noticesModel->tablePublic, $where, $upd_datetime, __('label.group'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            /**
             *
             */
            $result = $this->noticesModel->updateData($input);
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

    /**
     * Upload File
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @author hung.le 2024/07/15
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
     * Get filter column
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/22
     * @author hung.le
     */
    public function getFilterColumn(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $columnName = $request->column;
            if ($columnName == 'send_type') {
                $send_receive_name = [
                    ['send_type' => NOTICE_SEND_TYPE["USER_LOGIN"]],
                    ['send_type' => NOTICE_SEND_TYPE["NOT_USER_LOGIN"]],
                ];
                return Lib::returnJsonResult(true, '', $send_receive_name, count($send_receive_name));
            }
            $result = $this->noticesModel->getFilterColumn($columnName);
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $list = $result->toArray()['data'];
            $totalRow = $result->total();
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $list, $totalRow);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

}
