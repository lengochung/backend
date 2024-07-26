<?php
namespace App\Modules\Pages\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Models\PagesModel;

class PagesController extends BaseController {
    /**
     * The DivisionsModel instance.
     */
    protected $pagesModel;

    /**
     * Create a new DivisionsController instance.
     *
     * @return void
     */
    public function __construct(
        PagesModel $pagesModel
    ) {
        parent::__construct ();
        $this->pagesModel = $pagesModel;
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
            $result = $this->pagesModel->getAllList($request->all());
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

}
