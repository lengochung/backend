<?php
namespace App\Modules\Malfunctions\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Models\MalfunctionsModel;

class MalfunctionsController extends BaseController {
    /**
     * The MalfunctionsModel instance.
     */
    protected $malfunctionsModel;

    /**
     * Create a new DivisionsController instance.
     *
     * @return void
     */
    public function __construct(
        MalfunctionsModel $malfunctionsModel
    ) {
        parent::__construct ();
        $this->malfunctionsModel = $malfunctionsModel;
    }

    /**
     * Get malfunction list dropdown
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @date 2024/07/25
     * @author duy.pham
     */
    public function getMalfunctionsDropdown(Request $request) {
        try {
            $this->writeLog(__METHOD__);
            $totalRow = $this->malfunctionsModel->getTotalMalfunctionDropdown($request->all());
            if (empty($totalRow)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }
            $result = $this->malfunctionsModel->getMalfunctionDropdown($request->all());
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
