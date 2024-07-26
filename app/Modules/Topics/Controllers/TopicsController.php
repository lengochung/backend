<?php
namespace App\Modules\Topics\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Topics\Requests\TopicsRequest;
use App\Modules\Models\TopicsModel;
use App\Modules\Models\UserModel;
use App\Utils\Files;
use Illuminate\Support\Facades\Validator;

class TopicsController extends BaseController {
    /**
     * The TopicsModel instance.
     */
    protected $topicsModel;

    /**
     * Create a new TopicsController instance.
     *
     * @return void
     */
    public function __construct(
        TopicsModel $topicsModel
    ) {
        parent::__construct ();
        $this->topicsModel = $topicsModel;
    }

    /**
     * Check validation before save topic
     * @date 2024/07/05
     * @author hung.le
     * @param  \Illuminate\Http\Request     $request
     *
     * @return \Illuminate\Support\MessageBag|null
     */
    private function checkValidationDivision($request)
    {
        $currentRequest = new TopicsRequest();
        $validator = Validator::make($request->all(), $currentRequest->topicRules(), $currentRequest->topicMessages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return;
    }

    /**
     * Get all topics
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @date 2024/07/15
     * @author duy.pham
     */
    public function getAllList(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $totalRow = $this->topicsModel->getTotalAllList($request->all());
            if (empty($totalRow)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }
            $result = $this->topicsModel->getAllList($request->all());
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(false);
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $result, $totalRow);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }
    /**
     * Get topic detail
     * @date 2024/07/05
     * @author hung.le
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetail(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $topic_no = $request->topic_no;
            if (empty($topic_no)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.group')));
            }
            $data = $this->topicsModel->getById($topic_no);

            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $data);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Save topic from page notices
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
                'topic_no' => $request->topic_no,
                'office_id' => $request->office_id,
                'notice_no' => $request->notice_no,
                'topic_kbn' => $request->topic_kbn,
                'subject' => $request->subject,
                'event_date' => $request->event_date,
                'building_id' => $request->building_id,
                'fuel_type' => $request->fuel_type,
                'facility_id' => $request->facility_id,
                'facility_detail1' => $request->facility_detail1,
                'facility_detail2' => $request->facility_detail2,
                'previous_user_id' => $request->previous_user_id,
                'today_user_id' => $request->today_user_id,
                'detail' => $request->detail,
                'attached_file' => $request->attached_file,
                'is_deadline' => false,
                'completion_date' => $request->completion_date
            ];

            //Build data
            $input = Lib::assignData($this->topicsModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            unset($input["topic_no"]);
            $result = $this->topicsModel->insertData($input);
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
                $folderPath = UserModel::getUserLogin()['office_id'] . DIRECTORY_SEPARATOR . "topics" . DIRECTORY_SEPARATOR . $result->topic_no;
                $isSynchedFiles = Files::synchedFiles($request->synchedFilesData, $folderPath, true);
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
     * @author hung.le 2024/07/12 14:00
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
     * @author hung.le 2024/07/12 14:00
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
     * get filter column
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @date 2024/07/15
     * @author duy.pham
     */
    public function getFilterColumn(Request $request) {
        try {
            $this->writeLog(__METHOD__);

            $columnName = $request->column;

            if ($columnName == 'status') {
                $status = [
                    ['status' => TOPIC_STATUS["PRECAUTION"]],
                    ['status' => TOPIC_STATUS["CORRECTIVE"]],
                    ['status' => TOPIC_STATUS["MALFUNCTION"]],
                    ['status' => TOPIC_STATUS["DAILY_REPORT"]],
                ];
                return Lib::returnJsonResult(true, '', $status, count($status));
            }

            if (!Lib::checkValueExistInArray($this->topicsModel->getFillable(), $columnName)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(true, '', []);
            }

            $result = $this->topicsModel->getFilterColumn($columnName);
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
