<?php
namespace App\Modules\Correctives\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Models\DivisionsModel;
use App\Modules\Models\CorrectivesModel;
use Illuminate\Support\Facades\Validator;
use App\Modules\Models\CorrectivesDetailModel;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Correctives\Requests\CorrectivesRequest;

class CorrectivesController extends BaseController
{
    /**
     * The CorrectivesModel instance.
     */
    protected $correctivesModel;
    /**
     * The CorrectivesDetailModel instance.
     */
    protected $correctivesDetailModel;
    /**
     * The DivisionsModel instance.
     */
    protected $divisionsModel;

    /**
     * Create a new CorrectivesController instance.
     *
     * @return void
     */
    public function __construct(
        CorrectivesModel $correctivesModel,
        DivisionsModel $divisionsModel,
        CorrectivesDetailModel $correctivesDetailModel,
    ){
        parent::__construct ();
        $this->correctivesModel = $correctivesModel;
        $this->divisionsModel = $divisionsModel;
        $this->correctivesDetailModel = $correctivesDetailModel;
    }

    /**
     * get all corrective
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function getAllList(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);

            $totalRow = $this->correctivesModel->getTotalAllList($request->all(), true);
            if (empty($totalRow)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }

            $result = $this->correctivesModel->getAllList($request->all());
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
     * Get detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function getCorrectiveDetail(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $correctiveNo = $request->corrective_no;
            if (empty($correctiveNo)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.corrective')));
            }
            $data = $this->correctivesModel->getDetail($correctiveNo);

            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $data);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * create/update corrective info
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function onSave(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $isNew = $request->is_new ? true : false;
            $correctiveNo = $request->corrective_no;
            $updDatetime = $request->upd_datetime;

            //Check validation before save
            $checkValidation = $this->checkValidationCorrective($request);
            if (!is_null($checkValidation)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.invalid_validator'));
                return Lib::returnJsonResult(false, $checkValidation);
            }

            //Build data
            $correctiveData = Lib::assignData($this->correctivesModel->getFillable(), $request->all());
            if (!$correctiveData) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            $correctiveDetailData = Lib::assignData($this->correctivesDetailModel->getFillable(), $request->all());

            //add new
            if ($isNew) {
                $divCond = [
                    'function_name' => '是正処置',
                    'page_name' => '報告書',
                    'item_name' => 'ステータス',
                    'candidate' => '起票中',
                ];
                $openStatus = $this->divisionsModel->getDivisionId($divCond);
                if (!$openStatus) {
                    $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                    return Lib::returnJsonResult(false, sprintf(__('message.status_not_setup'), $divCond['candidate']));
                }

                $correctiveNo = $this->correctivesModel->generateCorrectiveNo();
                if (!$correctiveNo) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.generate_id_failed'), 3);
                    return Lib::returnJsonResult(false, __('message.create_failed'));
                }
                $correctiveData["corrective_no"] = $correctiveNo;
                $correctiveData['office_id'] = $this->getUserProperty('office_id');
                $correctiveData['edition_no'] = 1;
                $correctiveData['status'] = $openStatus->division_id;
                $result = $this->correctivesModel->insertData($correctiveData, $correctiveDetailData);
                if (empty($result)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.create_failed'), json_encode($request->all())), 3);
                    return Lib::returnJsonResult(false, __('message.create_failed'));
                }

                $rspData = true;
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $rspData);
            }

            //check multiple user update
            $where = ['corrective_no' => $correctiveNo];
            $errorMessage = $this->getMessageErrorMultiEdit('correctives', $where, $updDatetime, __('label.corrective'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            //update
            // $result = $this->correctivesModel->updateData($input, $correctiveNo);
            // if (empty($result)) {
            //     $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)), 3);
            //     return Lib::returnJsonResult(false, __('message.update_failed'));
            // }

            $rspData = "";
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $rspData);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Check validation before save
     *
     * @param  \Illuminate\Http\Request     $request
     *
     * @return \Illuminate\Support\MessageBag|null
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    private function checkValidationCorrective($request)
    {
        $currentRequest = new CorrectivesRequest();
        $validator = Validator::make($request->all(), $currentRequest->rules(), $currentRequest->messages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return;
    }

    /**
     * get filter column
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @date 2024/07/22
     * @author duy.pham
     */
    public function getFilterColumn(Request $request) {
        try {
            $this->writeLog(__METHOD__);

            $columnName = $request->column;

            if ($columnName == 'status_name') {
                $cond = [
                    "function_name" => "是正処置",
                    "page_name" => "報告書",
                    "item_name" => "ステータス",
                    "alias_column" => "status_name"
                ];
                $status = $this->divisionsModel->getDivisionsDropdown($cond, false);
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $status, count($status));
            }
            if ($columnName == 'office') {
                $offices = $this->correctivesModel->getFilterOffice();
                if (empty($offices)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                    return Lib::returnJsonResult(false, __('message.err_unknown'));
                }
                $list = $offices->toArray()['data'];
                $totalRow = $offices->total();
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $list, $totalRow);
            }
            if ($columnName == 'plan_deadline') {
                $offices = $this->correctivesModel->getFilterPlanDeadline();
                if (empty($offices)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                    return Lib::returnJsonResult(false, __('message.err_unknown'));
                }
                $list = $offices->toArray()['data'];
                $totalRow = $offices->total();
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $list, $totalRow);
            }


            if (!Lib::checkValueExistInArray($this->correctivesModel->getFillable(), $columnName)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(true, '', []);
            }

            $result = $this->correctivesModel->getFilterColumn($columnName);
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
