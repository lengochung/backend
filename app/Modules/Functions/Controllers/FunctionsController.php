<?php
namespace App\Modules\Functions\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Models\FunctionsModel;

class FunctionsController extends BaseController {
    /**
     * The DivisionsModel instance.
     */
    protected $functionsModel;

    /**
     * Create a new DivisionsController instance.
     *
     * @return void
     */
    public function __construct(
        FunctionsModel $functionsModel
    ) {
        parent::__construct ();
        $this->functionsModel = $functionsModel;
    }

    /**
     * Get all divisions
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllList(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $totalRow = $this->functionsModel->getAllListSize($request->all());
            if (empty($totalRow)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }
            $result = $this->functionsModel->getAllList($request->all());
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

}
