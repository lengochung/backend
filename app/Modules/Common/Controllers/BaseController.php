<?php
namespace App\Modules\Common\Controllers;
use Exception;
use App\Utils\Lib;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BaseController extends Controller
{
    public function __construct(){
        $this->_getApiRequest();
    }
    /**
     * Get user property
     * @param string $key
     * @return object|null
     */
    public static function getUserProperty($key)
    {
        if(empty($key)){
            return null;
        }
        $user = (!empty(Auth::guard(AUTH_API_GUARD_KEY)->user())) ? Auth::guard(AUTH_API_GUARD_KEY)->user(): null;
        if(isset($user -> $key)){
            return $user -> $key;
        }
        return null;
    }
    /**
     * Logs the details of an API request.
     *
     * This function retrieves the request data and URL, and logs them as debug information.
     *
     * @return void
     */
    private function _getApiRequest():void
    {
        $url = request()->url();
        Log::debug(__METHOD__ . " ===================== [START Request - API : $url] =====================");
        Log::debug(__METHOD__ . " [Request] =", [
            'url'           => $url,
            'data'          => request()->all()
        ]);
    }
    /**
     * Writes log messages to indicate the start or end of a method.
     *
     * @param string $method    The name of the method.
     * @param bool $isStart     Indicates whether it's the start or end of the method. Default is true (start).
     * @return void
     */
    public function writeLog($method, $isStart = true):void {
        if ($isStart) {
            Log::info($method . " START ");
        } else {
            Log::info($method . " END ");
        }
    }
    /**
     * Writes log messages with the specified log level.
     *
     * @param string $method  The name of the method.
     * @param string $message The log message to be written.
     * @param int $level The log level (1 for info, 2 for debug, 3 for error). Default is 1.
     * @param bool $isEnd
     * @return void
     */
    public function writeLogLevel($method, $message, $level = 1, $isEnd = true):void {
        if ($isEnd) {
            $message = ' END: '.$message;
        } else {
            $message = ': '.$message;
        }
        switch($level){
            case 1:
                Log::info($method.$message);
            break;
            case 2:
                Log::debug($method.$message);
            break;
            case 3:
                Log::error($method.$message);
            break;
            case 4:
                Log::warning($method.$message);
            break;
        }
    }

    /**
     * get filter column data
     *
     * @param string $tableStructure  table name
     * @param array $where  where array get data
     * @param string $columnName column name
     *
     * @return object|null
     *
     */
    public function getFilterColumnData($tableStructure, $where, $columnName) {
        try {
            $this->writeLog(__METHOD__);
            if (empty($tableStructure) || empty($where) || empty($columnName)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param'), 3);
                return null;
            }
            $query = DB::table("{$tableStructure}")
            ->select($columnName)
            ->where($where)
            ->groupBy($columnName)
            ->paginate(PAGINATE_LIMIT);

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $query;

        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * check edit data
     *
     * @param string $tableName  table name
     * @param array $where  where array get data
     * @param Date $updateDateTime  update datetime
     * @return array|null [
     *  'status' => number, 0: not exists, 1: can edit, 2: edit by other user, 3: delete by other user.
     *  'data' => string, user full name
     * ]
     */
    public function checkEditData($tableName, $where, $updateDateTime) {
        try {
            $this->writeLog(__METHOD__);
            if (empty($tableName) || empty($where)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param'), 3);
                return null;
            }
            if (empty($updateDateTime)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.empty_param'), "upd_datetime"), 3);
                return null;
            }
            $query = DB::table("{$tableName}")
            ->where($where);

            $result = DB::query()->fromSub($query, 'target')
            ->leftJoin("users as u", 'u.user_id', 'target.upd_user_id')
            ->select(
                'target.upd_datetime',
                'target.is_deleted'
            )
            ->selectRaw("CONCAT_WS(' ', u.user_first_name, u.user_last_name) as user_full_name")
            ->first();

            $response["data"] = "";
            if (!$result) {
                $response["status"] = 0;
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return $response;
            }
            if (!empty($result->is_deleted)) {
                $response["status"] = 3;
                $response["data"] = $result->user_full_name;
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return $response;
            }
            if (!Lib::compareDate($result->upd_datetime, $updateDateTime, 3)) {
                $response["status"] = 2;
                $response["data"] = $result->user_full_name;
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return $response;
            }

            $response["status"] = 1;
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $response;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return null;
        }
    }

    /**
     * get message error when multiple user edit
     *
     * @param string $tableName  table name
     * @param array $where  where array get data
     * @param Date|string $updateDateTime  update datetime
     * @param string $label  what is not found
     * @return string|null  null: can edit, string: error message
     */
    public function getMessageErrorMultiEdit($tableName, $where, $updateDateTime, $label = '') {
        try {
            $this->writeLog(__METHOD__);
            if (empty($tableName) || empty($where)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param'), 3);
                return __('message.err_unknown');
            }
            if (empty($updateDateTime)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.empty_param'), "upd_datetime"), 3);
                return __('message.err_unknown');
            }
            $query = DB::table("{$tableName}")
            ->where($where);

            $result = DB::query()->fromSub($query, 'target')
            ->leftJoin("users as u", 'u.user_id', 'target.upd_user_id')
            ->select(
                'target.upd_datetime',
                'target.is_deleted'
            )
            ->selectRaw("CONCAT_WS(' ', u.user_first_name, u.user_last_name) as user_full_name")
            ->first();

            if (!$result) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return sprintf(__('message.err_not_found'), $label);
            }
            if (!empty($result->is_deleted)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return sprintf(__('message.err_delete_by_other_user'), $result->user_full_name);
            }
            if (!Lib::compareDate($result->upd_datetime, $updateDateTime, 3)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return sprintf(__('message.err_multi_update'), $result->user_full_name);
            }

            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return __('message.err_unknown');
        }
    }

    /**
     * Get user roles
     *
     * @return object
     */
    public function getRole() {
        try {
            $this->writeLog(__METHOD__);
            $officeId = $this->getUserProperty('office_id');
            $userId = $this->getUserProperty('user_id');
            if(empty($officeId) || empty($userId)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.empty_param'), json_encode(["officeId" => $officeId, "userId" => $userId])), 3);
                return;
            }
            $role = DB::table("user-roles as urole")
            ->select(
                'r.role_id',
                'r.role_name',
                'r.read_role',
                'r.notice_role',
                'r.update_role',
                'r.approval_role',
                'r.manager_role',
                'r.leader_role',
                'r.admin_role',
            )
            ->join("roles as r", 'r.role_id', 'urole.role_id')
            ->where('urole.office_id', $officeId)
            ->where('urole.user_id', $userId)
            ->first();

            if (!$role) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.err_not_found'), json_encode(["officeId" => $officeId, "userId" => $userId])), 3);
                return;
            }
            return $role;
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return null;
        }
    }
}
