<?php
namespace App\Modules\Groups\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Models\UserModel;
use App\Modules\Models\GroupModel;
use App\Modules\Models\GroupTmpModel;
use App\Modules\Models\UserRoleModel;
use App\Modules\Models\GroupMemberModel;
use Illuminate\Support\Facades\Validator;
use App\Modules\Models\GroupMemberTmpModel;
use App\Modules\Groups\Requests\GroupRequest;
use App\Modules\Common\Controllers\BaseController;

class GroupsController extends BaseController
{
    /**
     * The GroupModel instance.
     */
    protected $groupModel;
    /**
     * The GroupTmpModel instance.
     */
    protected $groupTmpModel;
    /**
     * The UserModel instance.
     */
    protected $userModel;
    /**
     * The UserRoleModel instance.
     */
    protected $userRoleModel;
    /**
     * The GroupMemberModel instance.
     */
    protected $groupMemberModel;
    /**
     * The GroupMemberTmpModel instance.
     */
    protected $groupMemberTmpModel;

    /**
     * Create a new GroupsController instance.
     *
     * @return void
     */
    public function __construct(
        GroupModel $groupModel,
        GroupTmpModel $groupTmpModel,
        UserModel $userModel,
        UserRoleModel $userRoleModel,
        GroupMemberModel $groupMemberModel,
        GroupMemberTmpModel $groupMemberTmpModel,
    ){
        parent::__construct ();
        $this->groupModel = $groupModel;
        $this->groupTmpModel = $groupTmpModel;
        $this->userModel = $userModel;
        $this->userRoleModel = $userRoleModel;
        $this->groupMemberModel = $groupMemberModel;
        $this->groupMemberTmpModel = $groupMemberTmpModel;
    }

    /**
     * get all group
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllList(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            //get user role
            $role = $this->getRole();
            if (!$role) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $result = $this->groupModel->getAllList($request->all(), true, $role);
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $list = $result->toArray()['data'];

            $response["records"] = $list;
            $totalRow = $result->total();
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $response, $totalRow);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * get group detail by user role
     *
     * @param  $groupId group id
     * @return object
     */
    private function _getGroupDetail($groupId = null) {
        $this->writeLog(__METHOD__);
        if (!$groupId) {
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return;
        }
        //get user role
        $role = $this->getRole();
        if (!$role) {
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return;
        }
        $group = $this->groupModel->getDetail($groupId);
        if (!$group) {
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return;
        }
        // toArray get format date in $casts
        $group = $group->toArray();

        $isSendRequest = $group["request_user_id_tmp"] ? true : false;
        $isOwner = $group["add_user_id"] === $this->getUserProperty('user_id');

        //send request & (admin or approval)
        if ($isSendRequest && (($role->admin_role && $isOwner) || $role->approval_role)) {
            $data = [
                "group_id" => $group["group_id"],
                "office_id" => $group["office_id"],
                "group_name" => $group["group_name_tmp"],
                "description" => $group["description_tmp"],
                "request_user_id" => $group["request_user_id_tmp"],
                "request_user_name" => $group["request_user_name_tmp"],
                "request_date" => $group["request_date_tmp"],
                "approval1_user_id" => $group["approval1_user_id_tmp"],
                "approval1_user_name" => $group["approval1_user_name_tmp"],
                "approval1_date" => $group["approval1_date_tmp"],
                "approval1_comment" => $group["approval1_comment_tmp"],
                "approval2_user_id" => null,
                "approval2_user_name" => null,
                "approval2_date" => null,
                "approval2_comment" => null,
                "group_add_date" => $group["group_add_date"],
                "group_update_date" => $group["group_update_date"],
                "upd_datetime" => $group["upd_datetime"],
                "group_member_list" => $this->groupMemberTmpModel->getMemberList($groupId),
                "is_send_request" => 1,
                "is_approval" => $role->approval_role ? 1 : 0,
                "is_admin" => $role->admin_role ? 1: 0,
            ];
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $data;
        }
        //isAdmin
        if ($role->admin_role && $isOwner) {
            $data = [
                "group_id" => $group["group_id"],
                "office_id" => $group["office_id"],
                "group_name" => $group["group_name_tmp"],
                "description" => $group["description_tmp"],
                "request_user_id" => $group["request_user_id"],
                "request_user_name" => $group["request_user_name"],
                "request_date" => $group["request_date"],
                "approval1_user_id" => $group["approval1_user_id"],
                "approval1_user_name" => $group["approval1_user_name"],
                "approval1_date" => $group["approval1_date"],
                "approval1_comment" => $group["approval1_comment"],
                "approval2_user_id" => $group["approval2_user_id"],
                "approval2_user_name" => $group["approval2_user_name"],
                "approval2_date" => $group["approval2_date"],
                "approval2_comment" => $group["approval2_comment"],
                "group_add_date" => $group["group_add_date"],
                "group_update_date" => $group["group_update_date"],
                "upd_datetime" => $group["upd_datetime"],
                "group_member_list" => $this->groupMemberTmpModel->getMemberList($groupId),
                "is_admin" => 1,
                "is_approval" => $role->approval_role ? 1 : 0,
                "is_send_request" => 0,
            ];
            $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
            return $data;
        }
        //is other user
        $data = [
            "group_id" => $group["group_id"],
            "office_id" => $group["office_id"],
            "group_name" => $group["group_name"],
            "description" => $group["description"],
            "request_user_id" => $group["request_user_id"],
            "request_user_name" => $group["request_user_name"],
            "request_date" => $group["request_date"],
            "approval1_user_id" => $group["approval1_user_id"],
            "approval1_user_name" => $group["approval1_user_name"],
            "approval1_date" => $group["approval1_date"],
            "approval1_comment" => $group["approval1_comment"],
            "approval2_user_id" => $group["approval2_user_id"],
            "approval2_user_name" => $group["approval2_user_name"],
            "approval2_date" => $group["approval2_date"],
            "approval2_comment" => $group["approval2_comment"],
            "group_add_date" => $group["group_add_date"],
            "group_update_date" => $group["group_update_date"],
            "group_member_list" => $this->groupMemberModel->getMemberList($groupId),
            "is_admin" => 0,
            "is_approval" => $role->approval_role ? 1 : 0,
            "is_send_request" => $group["request_user_id_tmp"] ? 1 : 0,
        ];
        $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
        return $data;
    }

    /**
     * Get group detail
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupDetail(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $groupId = $request->group_id;
            if (empty($groupId)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_param'), 3);
                return Lib::returnJsonResult(false, sprintf(__('message.err_not_found'), __('label.group')));
            }
            $data = $this->_getGroupDetail($groupId);

            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $data);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * create/update group info
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onSave(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $isNew = $request->is_new ? true : false;
            $memberIdList = $request->member_id_list;
            $groupId = $request->group_id;
            $updDatetime = $request->upd_datetime;

            //Check validation before save
            $checkValidation = $this->checkValidationGroup($request);
            if (!is_null($checkValidation)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.invalid_validator'));
                return Lib::returnJsonResult(false, $checkValidation);
            }

            $inputRequest = [
                'group_id' => $request->group_id,
                'group_name' => $request->group_name,
                'description' => $request->description,
                'office_id' => $this->getUserProperty('office_id'),
            ];

            //Build data
            $input = Lib::assignData($this->groupModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $input["member_id_list"] = array_unique($memberIdList);

            //add new group
            if ($isNew) {
                unset($input["group_id"]);
                $result = $this->groupTmpModel->insertData($input);
                if (empty($result)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.create_failed'), json_encode($input)), 3);
                    return Lib::returnJsonResult(false, __('message.create_failed'));
                }

                $rspData = $this->_getGroupDetail($result->group_id);
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $rspData);
            }

            //check multiple user update
            $where = ['group_id' => $groupId];
            $errorMessage = $this->getMessageErrorMultiEdit('groups-tmp', $where, $updDatetime, __('label.group'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            //update group
            $result = $this->groupTmpModel->updateData($input, $groupId);
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)), 3);
                return Lib::returnJsonResult(false, __('message.update_failed'));
            }

            $rspData = $this->_getGroupDetail($groupId);
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $rspData);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Check validation before save group
     *
     * @param  \Illuminate\Http\Request     $request
     *
     * @return \Illuminate\Support\MessageBag|null
     */
    private function checkValidationGroup($request)
    {
        $currentRequest = new GroupRequest();
        $validator = Validator::make($request->all(), $currentRequest->groupRules(), $currentRequest->groupMessages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return;
    }

    /**
     * Delete
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onDelete(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $groupId = $request->group_id;
            $updDatetime = $request->upd_datetime;
            if (!$groupId) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_group_id'), 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (!$updDatetime) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' upd_datetime', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            //check multiple user update
            $where = ['group_id' => $groupId];
            $errorMessage = $this->getMessageErrorMultiEdit('groups', $where, $updDatetime, __('label.group'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            $result = $this->groupModel->deleteData($groupId);
            if (!$result) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.delete_failed'), $groupId), 3);
                return Lib::returnJsonResult(false, __('message.delete_failed'));
            }
            return Lib::returnJsonResult(true, "", true);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * cancel update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onCancel(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $groupId = $request->group_id;
            $updDatetime = $request->upd_datetime;
            if (!$groupId) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_group_id'), 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (!$updDatetime) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' upd_datetime', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            //check multiple user update
            $where = ['group_id' => $groupId];
            $errorMessage = $this->getMessageErrorMultiEdit('groups-tmp', $where, $updDatetime, __('label.group'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            $result = $this->groupTmpModel->cancelUpdate($groupId);
            if (!$result) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.cancel_failed'), $groupId), 3);
                return Lib::returnJsonResult(false, __('message.cancel_failed'));
            }

            $rspData = $this->_getGroupDetail($groupId);
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $rspData);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * send request to approver
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onSendApproval(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $groupId = $request->group_id;
            $updDatetime = $request->upd_datetime;
            if (!$groupId) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_group_id'), 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (!$updDatetime) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' upd_datetime', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            //check multiple user update
            $where = ['group_id' => $groupId];
            $errorMessage = $this->getMessageErrorMultiEdit('groups-tmp', $where, $updDatetime, __('label.group'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            $result = $this->groupTmpModel->sendRequest($groupId);
            if (!$result) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.send_approval_failed'), json_encode(['group_id' => $groupId])), 3);
                return Lib::returnJsonResult(false, __('message.send_approval_failed'));
            }

            $rspData = $this->_getGroupDetail($groupId);
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $rspData);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Approve group changed
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onApproval(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $groupId = $request->group_id;
            $updDatetime = $request->upd_datetime;
            $comment = !empty($request->comment) ? $request->comment : '';
            if (!$groupId) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_group_id'), 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (!$updDatetime) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' upd_datetime', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            //check multiple user update
            $where = ['group_id' => $groupId];
            $errorMessage = $this->getMessageErrorMultiEdit('groups-tmp', $where, $updDatetime, __('label.group'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            $result = $this->groupTmpModel->approval($groupId, $comment);
            if (!$result) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.approval_failed'), json_encode(['group_id' => $groupId])), 3);
                return Lib::returnJsonResult(false, __('message.approval_failed'));
            }

            $rspData = $this->_getGroupDetail($groupId);
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $rspData);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Reject change group
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onReject(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $groupId = $request->group_id;
            $updDatetime = $request->upd_datetime;
            if (!$groupId) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_group_id'), 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (!$updDatetime) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' upd_datetime', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            //check multiple user update
            $where = ['group_id' => $groupId];
            $errorMessage = $this->getMessageErrorMultiEdit('groups-tmp', $where, $updDatetime, __('label.group'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            $result = $this->groupTmpModel->reject($groupId);
            if (!$result) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.reject_failed'), json_encode(['group_id' => $groupId])), 3);
                return Lib::returnJsonResult(false, __('message.reject_failed'));
            }

            $rspData = $this->_getGroupDetail($groupId);

            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $rspData);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

}
