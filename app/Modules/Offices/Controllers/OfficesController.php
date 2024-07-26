<?php
namespace App\Modules\Offices\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Models\OfficeModel;
use App\Modules\Common\Controllers\BaseController;

class OfficesController extends BaseController
{
    /**
     * The OfficeModel instance.
     */
    protected $officeModel;

    /**
     * Create a new OfficesController instance.
     *
     * @return void
     */
    public function __construct(
        OfficeModel $officeModel
    ){
        parent::__construct ();
        $this->officeModel = $officeModel;
    }

    /**
     * get all office
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllList(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $result = $this->officeModel->getAllList($request->all());
            if (is_null($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $result);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

}
