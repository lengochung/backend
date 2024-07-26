<?php
namespace App\Modules\Roles\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Models\RoleModel;
use App\Modules\Models\UserModel;
use App\Modules\Common\Controllers\BaseController;

class RolesController extends BaseController
{
    /**
     * The RoleModel instance.
     */
    protected $roleModel;
    /**
     * The UserModel instance.
     */
    protected $userModel;

    /**
     * Create a new RolesController instance.
     *
     * @return void
     */
    public function __construct(
        RoleModel $roleModel,
        UserModel $userModel,
    ){
        parent::__construct ();
        $this->roleModel = $roleModel;
        $this->userModel = $userModel;
    }

    /**
     * get all role
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllList(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $result = $this->roleModel->getAllList($request->all());
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

    /**
     * create/update role
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onSave(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $isNew = $request->is_new ? true : false;
            $updDatetime = $request->upd_datetime;
            $roleId = $request->role_id;
            $inputRequest = [
                'role_id' => $request->role_id,
                'office_id' => $this->getUserProperty('office_id'),
                'role_name' => !empty($request->role_name) ? trim($request->role_name) : '',
                'read_role' => $request->read_role,
                'notice_role' => $request->notice_role,
                'update_role' => $request->update_role,
                'approval_role' => $request->approval_role,
                'manager_role' => $request->manager_role,
                'leader_role' => $request->leader_role,
                'admin_role' => $request->admin_role,
            ];

            //Build data
            $input = Lib::assignData($this->roleModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            //check duplicate role name
            $isDuplicateRoleName = $this->roleModel->isDuplicateRoleName(
                $inputRequest['office_id'],
                $inputRequest['role_name'],
                $isNew ? null : $inputRequest['role_id']
            );

            if ($isDuplicateRoleName) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false, sprintf(__('message.item_exists'), $inputRequest['role_name']));
            }

            if ($isNew) {
                unset($input['role_id']);
                $result = $this->roleModel->insertData($input);
                if (empty($result)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.create_failed'), json_encode($input)), 3);
                    return Lib::returnJsonResult(false, __('message.create_failed'));
                }
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $result);
            }

            $where = ['role_id' => $roleId];
            $errorMessage = $this->getMessageErrorMultiEdit('roles', $where, $updDatetime, __('label.role'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            //update
            $result = $this->roleModel->updateData($input, $input['role_id']);
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
     * delete role
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onDelete(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $roleId = $request->role_id;
            $updDatetime = $request->upd_datetime;
            if (!$roleId) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' role_id', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (!$updDatetime) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' upd_datetime', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            //check multiple user update
            $where = ['role_id' => $roleId];
            $errorMessage = $this->getMessageErrorMultiEdit('roles', $where, $updDatetime, __('label.role'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            $result = $this->roleModel->deleteData($roleId);
            if (!$result) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.delete_failed'), $roleId), 3);
                return Lib::returnJsonResult(false, __('message.delete_failed'));
            }
            return Lib::returnJsonResult(true, "", true);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Get role current user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoleCurrentUser(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            //get user role
            $role = $this->getRole();
            if (!$role) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $role);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

}
