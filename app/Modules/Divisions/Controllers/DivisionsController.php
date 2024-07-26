<?php
namespace App\Modules\Divisions\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Divisions\Requests\DivisionsRequest;
use App\Modules\Models\DivisionsModel;
use App\Modules\Models\FunctionsModel;
use App\Modules\Models\ItemsModel;
use App\Modules\Models\PagesModel;
use Illuminate\Support\Facades\Validator;

class DivisionsController extends BaseController {
    /**
     * The DivisionsModel instance.
     */
    protected $divisionsModel;
    protected $functionsModel;
    protected $pagesModel;
    protected $itemsModel;

    /**
     * Create a new DivisionsController instance.
     *
     * @return void
     */
    public function __construct(
        DivisionsModel $divisionsModel,
        FunctionsModel $functionsModel,
        PagesModel $pagesModel,
        ItemsModel $itemsModel
    ) {
        parent::__construct ();
        $this->divisionsModel = $divisionsModel;
        $this->functionsModel = $functionsModel;
        $this->pagesModel = $pagesModel;
        $this->itemsModel = $itemsModel;
    }

    /**
     * Check validation before save division
     *
     * @param  \Illuminate\Http\Request     $request
     *
     * @return \Illuminate\Support\MessageBag|null
     * @date 2024/07/05
     * @author hung.le
     */
    private function checkValidationDivision($request)
    {
        $currentRequest = new DivisionsRequest();
        $validator = Validator::make($request->all(), $currentRequest->rules(), $currentRequest->messages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return;
    }

    /**
     * Get all divisions
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
     */
    public function getAllList(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $totalRow = $this->divisionsModel->getAllListSize($request->all());
            if (empty($totalRow)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }
            $result = $this->divisionsModel->getAllList($request->all());
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
     * Get list divisions filter search
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/23
     * @author hung.le
     */
    public function getListFilterSearch(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $totalRow = $this->divisionsModel->getListFilterSearchSize($request->all());
            if (empty($totalRow)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }
            $result = $this->divisionsModel->getListFilterSearch($request->all());
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
     * Get list division status by page_no
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
     */
    public function getDivisionsByPageNo(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $cond = [
                'page_id' => $request->page_id,
                'item_name' => DB_ITEM_NAME_VALUES['item_name_status']
            ];
            $result = $this->divisionsModel->getDivisionsByPageNo($cond);
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
     * Save division
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
            // Check exist function_no, page_no, item_no in database
            // if false => return
            $functionE = $this->functionsModel->getById($request->function_id);
            if(!$functionE) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.no_data'));
                return Lib::returnJsonResult(false, __("message.error_division_id", [
                    "attribute" => __("label.function_id"),
                    "page" => __("label.page_division"),
                ]));
            }
            $pageE = $this->pagesModel->getById($request->page_id);
            if(!$pageE) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.no_data'));
                return Lib::returnJsonResult(false, __("message.error_division_id", [
                    "attribute" => __("label.page_id"),
                    "page" => __("label.page_division"),
                ]));
            }
            $itemE = $this->itemsModel->getById($request->item_id);
            if(!$itemE) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.no_data'));
                return Lib::returnJsonResult(false, __("message.error_division_id", [
                    "attribute" => __("label.item_id"),
                    "page" => __("label.page_division"),
                ]));
            }
            // Builder Input insert/update
            $inputRequest = [
                'division_id' => $request->division_id,
                'function_id' => $request->function_id,
                'page_id' => $request->page_id,
                'item_id' => $request->item_id,
                'candidate' => $request->candidate
            ];
            //Build data
            $input = Lib::assignData($this->divisionsModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            /**
             * Save/Update
             */
            $isNew = $request->is_new ? true : false;
            if ($isNew) {
                unset($input["division_id"]);
                $result = $this->divisionsModel->insertData($input);
                if (empty($result)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.create_failed'), json_encode($input)), 3);
                    return Lib::returnJsonResult(false, __('message.create_failed'));
                }
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $result);
            }
            /**
             * Recheck division_id after update
             */
            if(!isset($input["division_id"]) || $input["division_id"] < 0) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)), 3);
                return Lib::returnJsonResult(false, __('message.update_failed'));
            }
            // Check multiple user update
            $where = [
                'division_id' => $request->division_id,
            ];
            $upd_datetime = $request->upd_datetime;
            $errorMessage = $this->getMessageErrorMultiEdit($this->divisionsModel->table, $where, $upd_datetime, __('label.group'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }
            // Update
            $result = $this->divisionsModel->updateData($input);
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
     * Delete division by Id from request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
     */
    public function onDelete(Request $request) {
        try {
            $this->writeLog(__METHOD__);

            $division_id = $request->division_id;

            if (!$division_id || $division_id == 0) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.delete_failed'));
            }

            //update group
            $result = $this->divisionsModel->deleteData($division_id);
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.delete_failed'), $division_id), 3);
                return Lib::returnJsonResult(false, __('message.delete_failed'));
            }

            $this->writeLog(__METHOD__, false);

            return Lib::returnJsonResult(true, "", true);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.delete_failed'));
        }
    }

    /**
     * Get division_id detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/05
     * @author hung.le
     */
    public function getDetail(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $division_id = $request->division_id;
            if (empty($division_id)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.group')));
            }
            $data = $this->divisionsModel->getById($division_id);

            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $data);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Get filter column
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @date 2024/07/15
     * @author hung.le
     */
    public function getFilterColumn(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $columnName = $request->column;
            // if (!Lib::checkValueExistInArray($this->divisionsModel->getFillable(), $columnName)) {
            //     $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
            //     return Lib::returnJsonResult(true, '', []);
            // }
            $result = $this->divisionsModel->getFilterColumn($columnName);
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
    /**
     * Get list division dropdown
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @date 2024/07/24
     * @author duy.pham
     */
    public function getDivisionsDropdown(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $type = $request->type;
            $page = !empty($request->page) ? $request->page : 0;
            $keyword = $request->keyword;

            if (empty($type)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }

            $param = [
                'function_name' => '',
                'page_name' => '',
                'item_name' => ''
            ];

            switch ($type) {
                //是正処置@報告書@ステータス
                case 1:
                    $param = $this->_getParamDivisionDropdown('是正処置', '報告書', 'ステータス');
                    break;
                //是正処置@報告書@事象分類
                case 2:
                    $param = $this->_getParamDivisionDropdown('是正処置', '報告書', '事象分類');
                    break;
                //是正処置@報告書@該当障害報告
                case 3:
                    $param = $this->_getParamDivisionDropdown('是正処置', '報告書', '該当障害報告');
                    break;
                //是正処置@報告書@要否判定
                case 4:
                    $param = $this->_getParamDivisionDropdown('是正処置', '報告書', '要否判定');
                    break;
                //是正処置@報告書@効果判定
                case 5:
                    $param = $this->_getParamDivisionDropdown('是正処置', '報告書', '効果判定');
                    break;
                //予防処置@報告書@ステータス
                case 6:
                    $param = $this->_getParamDivisionDropdown('予防処置', '報告書', 'ステータス');
                    break;
                //予防処置@報告書@情報源
                case 7:
                    $param = $this->_getParamDivisionDropdown('予防処置', '報告書', '情報源');
                    break;
                //予防処置@報告書@事象分類
                case 8:
                    $param = $this->_getParamDivisionDropdown('予防処置', '報告書', '事象分類');
                    break;
                //予防処置@報告書@該当障害報告
                case 9:
                    $param = $this->_getParamDivisionDropdown('予防処置', '報告書', '該当障害報告');
                    break;
                //予防処置@報告書@要否判定
                case 10:
                    $param = $this->_getParamDivisionDropdown('予防処置', '報告書', '要否判定');
                    break;
                //予防処置@報告書@効果判定
                case 11:
                    $param = $this->_getParamDivisionDropdown('予防処置', '報告書', '効果判定');
                    break;
                //障害報告・管理@報告書@ステータス
                case 12:
                    $param = $this->_getParamDivisionDropdown('障害報告・管理', '報告書', 'ステータス');
                    break;
                //障害報告・管理@報告書@障害ランク
                case 13:
                    $param = $this->_getParamDivisionDropdown('障害報告・管理', '報告書', '障害ランク');
                    break;
                //障害報告・管理@報告書@要否判定
                case 14:
                    $param = $this->_getParamDivisionDropdown('障害報告・管理', '報告書', '要否判定');
                    break;
                //障害報告・管理@報告書@効果判定
                case 15:
                    $param = $this->_getParamDivisionDropdown('障害報告・管理', '報告書', '効果判定');
                    break;
                //操業日報@操業日報@ステータス
                case 16:
                    $param = $this->_getParamDivisionDropdown('操業日報', '操業日報', 'ステータス');
                    break;
                //操業日報@操業日報@トピック区分
                case 17:
                    $param = $this->_getParamDivisionDropdown('操業日報', '操業日報', 'トピック区分');
                    break;
                //操業日報@操業日報@引継要否
                case 18:
                    $param = $this->_getParamDivisionDropdown('操業日報', '操業日報', '引継要否');
                    break;
                //申し送り@申し送り@ステータス
                case 19:
                    $param = $this->_getParamDivisionDropdown('申し送り', '申し送り', 'ステータス');
                    break;
                //内製作業@計画・報告書@ステータス
                case 20:
                    $param = $this->_getParamDivisionDropdown('内製作業', '計画・報告書', 'ステータス');
                    break;
                //内製作業@計画・報告書@作業結果
                case 21:
                    $param = $this->_getParamDivisionDropdown('内製作業', '計画・報告書', '作業結果');
                    break;
                //工事情報周知@管理票@ステータス
                case 22:
                    $param = $this->_getParamDivisionDropdown('工事情報周知', '管理票', 'ステータス');
                    break;
                //工事情報周知@管理票@計画分類
                case 23:
                    $param = $this->_getParamDivisionDropdown('工事情報周知', '管理票', '計画分類');
                    break;
                //工事情報周知@管理票@工事種別
                case 24:
                    $param = $this->_getParamDivisionDropdown('工事情報周知', '管理票', '工事種別');
                    break;
                //工事情報周知@管理票@工事計画書
                case 25:
                    $param = $this->_getParamDivisionDropdown('工事情報周知', '管理票', '工事計画書');
                    break;
                //工事情報周知@管理票@原動影響
                case 26:
                    $param = $this->_getParamDivisionDropdown('工事情報周知', '管理票', '原動影響');
                    break;
                //工事情報周知@管理票@センサー誤報対応
                case 27:
                    $param = $this->_getParamDivisionDropdown('工事情報周知', '管理票', 'センサー誤報対応');
                    break;
                //工事情報周知@管理票@入館手続き
                case 28:
                    $param = $this->_getParamDivisionDropdown('工事情報周知', '管理票', '入館手続き');
                    break;
                //工事情報周知@管理票@通行・作業エリア規制
                case 29:
                    $param = $this->_getParamDivisionDropdown('工事情報周知', '管理票', '通行・作業エリア規制');
                    break;
                //工事情報周知@管理票@危険予知対策
                case 30:
                    $param = $this->_getParamDivisionDropdown('工事情報周知', '管理票', '危険予知対策');
                    break;
                //工事情報周知@管理票@完了
                case 31:
                    $param = $this->_getParamDivisionDropdown('工事情報周知', '管理票', '完了');
                    break;
                //電力デマンド管理@初期設定票@ステータス
                case 32:
                    $param = $this->_getParamDivisionDropdown('電力デマンド管理', '初期設定票', 'ステータス');
                    break;
                //電力デマンド管理@管理票@ステータス
                case 33:
                    $param = $this->_getParamDivisionDropdown('電力デマンド管理', '管理票', 'ステータス');
                    break;
                //電力デマンド管理@管理票@帳票区分
                case 34:
                    $param = $this->_getParamDivisionDropdown('電力デマンド管理', '管理票', '帳票区分');
                    break;
                //電力デマンド管理@管理票@デマンド予測カーブ確認結果
                case 35:
                    $param = $this->_getParamDivisionDropdown('電力デマンド管理', '管理票', 'デマンド予測カーブ確認結果');
                    break;
                //電力デマンド管理@管理票@天気
                case 36:
                    $param = $this->_getParamDivisionDropdown('電力デマンド管理', '管理票', '天気');
                    break;
                //電力デマンド管理@管理票@デマンド管理期間の終了判定
                case 37:
                    $param = $this->_getParamDivisionDropdown('電力デマンド管理', '管理票', 'デマンド管理期間の終了判定');
                    break;
                //標準文書管理@管理記録@ステータス
                case 38:
                    $param = $this->_getParamDivisionDropdown('標準文書管理', '管理記録', 'ステータス');
                    break;
                //標準文書管理@管理記録@標準分類
                case 39:
                    $param = $this->_getParamDivisionDropdown('標準文書管理', '管理記録', '標準分類');
                    break;
                //標準文書管理@管理記録@維持/廃止判定
                case 40:
                    $param = $this->_getParamDivisionDropdown('標準文書管理', '管理記録', '維持/廃止判定');
                    break;
                //標準文書管理@管理記録@改訂要否
                case 41:
                    $param = $this->_getParamDivisionDropdown('標準文書管理', '管理記録', '改訂要否');
                    break;
                //分析管理@項目一覧@ステータス
                case 42:
                    $param = $this->_getParamDivisionDropdown('分析管理', '項目一覧', 'ステータス');
                    break;
                //分析管理@項目編集@3σ管理
                case 43:
                    $param = $this->_getParamDivisionDropdown('分析管理', '項目編集', '3σ管理');
                    break;
                //分析管理@項目編集@3σ算定期間
                case 44:
                    $param = $this->_getParamDivisionDropdown('分析管理', '項目編集', '3σ算定期間');
                    break;
                //分析管理@項目編集@見直し周期
                case 45:
                    $param = $this->_getParamDivisionDropdown('分析管理', '項目編集', '見直し周期');
                    break;
                //分析管理@項目編集@分析頻度
                case 46:
                    $param = $this->_getParamDivisionDropdown('分析管理', '項目編集', '分析頻度');
                    break;
                //分析管理@結果入力@グループ選択
                case 47:
                    $param = $this->_getParamDivisionDropdown('分析管理', '結果入力', 'グループ選択');
                    break;
                //分析管理@結果入力@判定
                case 48:
                    $param = $this->_getParamDivisionDropdown('分析管理', '結果入力', '判定');
                    break;
                //分析管理@結果入力@所見コメント
                case 49:
                    $param = $this->_getParamDivisionDropdown('分析管理', '結果入力', '所見コメント');
                    break;
                //点検データ記録・管理@項目一覧@ステータス
                case 50:
                    $param = $this->_getParamDivisionDropdown('点検データ記録・管理', '項目一覧', 'ステータス');
                    break;
                //点検データ記録・管理@項目編集@3σ管理
                case 51:
                    $param = $this->_getParamDivisionDropdown('点検データ記録・管理', '項目編集', '3σ管理');
                    break;
                //点検データ記録・管理@項目編集@3σ算定期間
                case 52:
                    $param = $this->_getParamDivisionDropdown('点検データ記録・管理', '項目編集', '3σ算定期間');
                    break;
                //点検データ記録・管理@項目編集@見直し周期
                case 53:
                    $param = $this->_getParamDivisionDropdown('点検データ記録・管理', '項目編集', '見直し周期');
                    break;
                //点検データ記録・管理@項目編集@点検頻度
                case 54:
                    $param = $this->_getParamDivisionDropdown('点検データ記録・管理', '項目編集', '点検頻度');
                    break;
                //点検データ記録・管理@結果入力@グループ選択
                case 55:
                    $param = $this->_getParamDivisionDropdown('点検データ記録・管理', '結果入力', 'グループ選択');
                    break;
                //点検データ記録・管理@結果入力@判定
                case 56:
                    $param = $this->_getParamDivisionDropdown('点検データ記録・管理', '結果入力', '判定');
                    break;
                //点検データ記録・管理@結果入力@所見コメント
                case 57:
                    $param = $this->_getParamDivisionDropdown('点検データ記録・管理', '結果入力', '所見コメント');
                    break;
                //トレンド管理@項目一覧@ステータス
                case 58:
                    $param = $this->_getParamDivisionDropdown('トレンド管理', '項目一覧', 'ステータス');
                    break;
                //トレンド管理@項目編集@3σ管理
                case 59:
                    $param = $this->_getParamDivisionDropdown('トレンド管理', '項目編集', '3σ管理');
                    break;
                //トレンド管理@項目編集@3σ算定期間
                case 60:
                    $param = $this->_getParamDivisionDropdown('トレンド管理', '項目編集', '3σ算定期間');
                    break;
                //トレンド管理@項目編集@見直し周期
                case 61:
                    $param = $this->_getParamDivisionDropdown('トレンド管理', '項目編集', '見直し周期');
                    break;
                //トレンド管理@項目編集@確認頻度
                case 62:
                    $param = $this->_getParamDivisionDropdown('トレンド管理', '項目編集', '確認頻度');
                    break;
                //トレンド管理@結果入力@グループ選択
                case 63:
                    $param = $this->_getParamDivisionDropdown('トレンド管理', '結果入力', 'グループ選択');
                    break;
                //トレンド管理@結果入力@判定
                case 64:
                    $param = $this->_getParamDivisionDropdown('トレンド管理', '結果入力', '判定');
                    break;
                //トレンド管理@結果入力@所見コメント
                case 65:
                    $param = $this->_getParamDivisionDropdown('トレンド管理', '結果入力', '所見コメント');
                    break;
                //表示・掲示物管理@管理票@ステータス
                case 66:
                    $param = $this->_getParamDivisionDropdown('表示・掲示物管理', '管理票', 'ステータス');
                    break;
                //表示・掲示物管理@管理票@種類
                case 67:
                    $param = $this->_getParamDivisionDropdown('表示・掲示物管理', '管理票', '種類');
                    break;
                //表示・掲示物管理@管理票@終了可否判定
                case 68:
                    $param = $this->_getParamDivisionDropdown('表示・掲示物管理', '管理票', '終了可否判定');
                    break;
                //変更指示書@変更指示書@ステータス
                case 69:
                    $param = $this->_getParamDivisionDropdown('変更指示書', '変更指示書', 'ステータス');
                    break;
                //変更指示書@変更指示書@終了可否判定
                case 70:
                    $param = $this->_getParamDivisionDropdown('変更指示書', '変更指示書', '終了可否判定');
                    break;
                //変更指示書@変更指示書@恒久化要否判定
                case 71:
                    $param = $this->_getParamDivisionDropdown('変更指示書', '変更指示書', '恒久化要否判定');
                    break;
                //部品・材料在庫管理@項目一覧@単位：標準納期・使用期限
                case 72:
                    $param = $this->_getParamDivisionDropdown('部品・材料在庫管理', '項目一覧', '単位：標準納期・使用期限');
                    break;
                //部品・材料在庫管理@項目一覧@単位：数量
                case 73:
                    $param = $this->_getParamDivisionDropdown('部品・材料在庫管理', '項目一覧', '単位：数量');
                    break;
                //部品・材料在庫管理@項目一覧@ステータス
                case 74:
                    $param = $this->_getParamDivisionDropdown('部品・材料在庫管理', '項目一覧', 'ステータス');
                    break;
                //部品・材料発注管理@項目一覧@単位：数量
                case 75:
                    $param = $this->_getParamDivisionDropdown('部品・材料発注管理', '項目一覧', '単位：数量');
                    break;
                //部品・材料発注管理@項目一覧@判定
                case 76:
                    $param = $this->_getParamDivisionDropdown('部品・材料発注管理', '項目一覧', '判定');
                    break;
                //メンテナンス管理@項目一覧@ステータス
                case 77:
                    $param = $this->_getParamDivisionDropdown('メンテナンス管理', '項目一覧', 'ステータス');
                    break;
                //メンテナンス管理@項目一覧@実施基準
                case 78:
                    $param = $this->_getParamDivisionDropdown('メンテナンス管理', '項目一覧', '実施基準');
                    break;
                //教育・訓練@実施報告書@ステータス
                case 79:
                    $param = $this->_getParamDivisionDropdown('教育・訓練', '実施報告書', 'ステータス');
                    break;
                //教育・訓練@実施報告書@免許証
                case 80:
                    $param = $this->_getParamDivisionDropdown('教育・訓練', '実施報告書', '免許証');
                    break;
                //教育・訓練@実施報告書@身上書
                case 81:
                    $param = $this->_getParamDivisionDropdown('教育・訓練', '実施報告書', '身上書');
                    break;
                //教育・訓練@実施報告書@資格管理一覧表
                case 82:
                    $param = $this->_getParamDivisionDropdown('教育・訓練', '実施報告書', '資格管理一覧表');
                    break;
                //教育・訓練@実施報告書@資格認定
                case 83:
                    $param = $this->_getParamDivisionDropdown('教育・訓練', '実施報告書', '資格認定');
                    break;
                //教育・訓練@実施報告書@教育履歴登録
                case 84:
                    $param = $this->_getParamDivisionDropdown('教育・訓練', '実施報告書', '教育履歴登録');
                    break;
                //教育・訓練@実施報告書@登録担当確認
                case 85:
                    $param = $this->_getParamDivisionDropdown('教育・訓練', '実施報告書', '登録担当確認');
                    break;
                //マスタ管理@ユーザ登録・権限管理@権限設定
                case 86:
                    $param = $this->_getParamDivisionDropdown('マスタ管理', 'ユーザ登録・権限管理', '権限設定');
                    break;
                //トップ画面類@帳票照会@回送/承認状況
                case 87:
                    $param = $this->_getParamDivisionDropdown('トップ画面類', '帳票照会', '回送/承認状況');
                    break;
                //共通@共通@建屋
                case 88:
                    $param = $this->_getParamDivisionDropdown('共通', '共通', '建屋');
                    break;
                //共通@原動種類@電気設備
                case 89:
                    $param = $this->_getParamDivisionDropdown('共通', '原動種類', '電気設備');
                    break;
                //共通@原動種類@空調設備
                case 90:
                    $param = $this->_getParamDivisionDropdown('共通', '原動種類', '空調設備');
                    break;
                //共通@原動種類@冷熱源設備
                case 91:
                    $param = $this->_getParamDivisionDropdown('共通', '原動種類', '冷熱源設備');
                    break;
                //共通@原動種類@諸配管設備
                case 92:
                    $param = $this->_getParamDivisionDropdown('共通', '原動種類', '諸配管設備');
                    break;
                //共通@原動種類@水処理設備
                case 93:
                    $param = $this->_getParamDivisionDropdown('共通', '原動種類', '水処理設備');
                    break;
                //共通@原動種類@薬品設備
                case 94:
                    $param = $this->_getParamDivisionDropdown('共通', '原動種類', '薬品設備');
                    break;
                //共通@原動種類@ガス設備
                case 95:
                    $param = $this->_getParamDivisionDropdown('共通', '原動種類', 'ガス設備');
                    break;
                //共通@原動種類@その他
                case 96:
                    $param = $this->_getParamDivisionDropdown('共通', '原動種類', 'その他');
                    break;

                default:
                    $this->writeLog(__METHOD__, false);
                    return Lib::returnJsonResult(false);
            }

            $param['page'] = $page;
            $param['keyword'] = $keyword;
            $totalRow = $this->divisionsModel->getTotalDivisionsDropdown($param);
            if (empty($totalRow)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }
            $result = $this->divisionsModel->getDivisionsDropdown($param);
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
     * get param division dropdown
     *
     * @param  string  $functionName function name
     * @param  string  $pageName page name
     * @param  string  $itemName item name
     * @return array [
     *  'function_name' => string,
     *  'page_name' => string,
     *  'item_name' => string
     * ]
     *
     * @date 2024/07/25
     * @author duy.pham
     */
    private function _getParamDivisionDropdown($functionName = '', $pageName = '', $itemName = '') {
        return [
            'function_name' => $functionName,
            'page_name' => $pageName,
            'item_name' => $itemName
        ];
    }

}
