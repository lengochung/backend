<?php
namespace App\Modules\Users\Controllers;

use Exception;
use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Models\UserModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Modules\Models\UserRoleModel;
use Illuminate\Support\Facades\Validator;
use App\Modules\Users\Requests\UserRequest;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Models\UserAndGroupModel;

class UsersController extends BaseController
{
    /**
     * The UserModel instance.
     */
    protected $userModel;
    /**
     * The UserRoleModel instance.
     */
    protected $userRoleModel;
    /**
     * The userAndGroupModel instance.
     */
    protected $userAndGroupModel;

    /**
     * Create a new UsersController instance.
     *
     * @return void
     */
    public function __construct(
        UserModel $userModel,
        UserRoleModel $userRoleModel,
        UserAndGroupModel $userAndGroupModel
    ){
        parent::__construct ();
        $this->userModel = $userModel;
        $this->userRoleModel = $userRoleModel;
        $this->userAndGroupModel = $userAndGroupModel;
    }

    /**
     * Get user by id
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserInfo(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $userId = $request->user_id;
            if (empty($userId)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.empty_user_id'), 4);
                return Lib::returnJsonResult(false, __('message.user_not_found'));
            }
            $userInfo = $this->userModel->getUserInfo($userId);
            if (!$userInfo) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.empty_user_info'), $userId));
                return Lib::returnJsonResult(false, __('message.user_not_found'));
            }
            $userRoleList = $this->userRoleModel->getUserRoleByGroupOffice($userInfo['group_office_id'], $userId);
            $userInfo['role_list'] = $userRoleList;
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $userInfo);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Update user profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onUpdateUserProfile(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $isUpdatePass = !Lib::isBlank($request->new_password);
            $updDatetime = $request->upd_datetime;
            $userId = $this->getUserProperty('user_id');

            //Check validation before save
            $checkValidation = $this->checkValidationUpdateProfile($request, $isUpdatePass);
            if (!is_null($checkValidation)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.invalid_validator'));
                return Lib::returnJsonResult(false, $checkValidation);
            }

            $inputRequest = [
                'user_first_name' => $request->user_first_name,
                'user_last_name' => $request->user_last_name,
                'employee_no' => $request->employee_no,
                'affiliation' => $request->affiliation,
                'position' => $request->position,
                'mail' => $request->mail,
            ];

            //Build data
            $input = Lib::assignData($this->userModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $input['user_id'] = $userId;

            //check multiple user update
            $where = ['user_id' => $userId];
            $errorMessage = $this->getMessageErrorMultiEdit('users', $where, $updDatetime, __('label.user'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            //check mail use by other user
            $isMailExists = $this->userModel->isMailExists($input['mail'], $userId);
            if ($isMailExists) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.err_mail_exists'), 3);
                return Lib::returnJsonResult(false, __('message.err_mail_exists'));
            }

            $userInfo = $this->userModel->getById($userId);
            if ($isUpdatePass) {
                if (!Hash::check($request->password, $userInfo->password)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.wrong_pass'));
                    return Lib::returnJsonResult(false, __('message.err_pass_incorrect'));
                }
                $input['password'] = Hash::make(md5($request->new_password));
                $input['password_update_date'] = Lib::getCurrentDate();
            }
            $input['update_date'] = Lib::getCurrentDate();
            $result = $this->userModel->updateData($input, $input['user_id'], false);

            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)), 3);
                return Lib::returnJsonResult(false, __('message.update_failed'));
            }
            $userInfo = $this->userModel->getUserInfo($input['user_id']);
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $userInfo);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Check validation before save profile
     *
     * @param  \Illuminate\Http\Request     $request
     * @param  boolean     $isUpdatePass
     *
     * @return \Illuminate\Support\MessageBag|null
     */
    private function checkValidationUpdateProfile($request, $isUpdatePass)
    {
        $currentRequest = new UserRequest();
        $validator = Validator::make($request->all(), $currentRequest->updateProfileRules($isUpdatePass), $currentRequest->updateProfileMessages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return;
    }

    /**
     * search all user list
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllList(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $totalRow = $this->userModel->getTotalUsers($request->all());
            if (empty($totalRow)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }
            $results = $this->userModel->getAllList($request->all());
            if (empty($results)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.no_data'));
                return Lib::returnJsonResult(false);
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $results, $totalRow);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false);
        }
    }

    /**
     * search user list by userIds
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserListByUserIds(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            if(empty($request->user_ids)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $result = $this->userModel->getUserListByUserIds($request->all());
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            $list = $result;
            $totalRow = $result->count();
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $list, $totalRow);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * search user list union group list
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserListUnionGroupList(Request $request) {
        $this->writeLog(__METHOD__);
        $totalRow = $this->userAndGroupModel->getUserListUnionGroupListSize($request->all());
        if (empty($totalRow)) {
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(false);
        }
        $result = $this->userAndGroupModel->getUserListUnionGroupList($request->all(), true);
        if (empty($result)) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' ,__('log.get_data_failed'), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
        $this->writeLog(__METHOD__, false);
        return Lib::returnJsonResult(true, '', $result, $totalRow);
    }

    /**
     * create/update user info
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onSave(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $isNew = $request->is_new ? true : false;
            $userId = $request->user_id;
            $updDatetime = $request->upd_datetime;

            //Check validation before save
            $checkValidation = $this->checkValidationUser($request);
            if (!is_null($checkValidation)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.invalid_validator'));
                return Lib::returnJsonResult(false, $checkValidation);
            }

            $inputRequest = [
                'user_id' => $request->user_id,
                'office_id' => $request->office_id,
                'user_first_name' => $request->user_first_name,
                'user_last_name' => $request->user_last_name,
                'employee_no' => $request->employee_no,
                'affiliation' => $request->affiliation,
                'position' => $request->position,
                'mail' => $request->mail,
            ];

            //Build data
            $input = Lib::assignData($this->userModel->getFillable(), $inputRequest);
            if (!$input) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', __('log.err_assign_data'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            $input['update_date'] = Lib::getCurrentDate();
            $input['role_list'] = $request->role_list;

            //check mail used by other user
            $isMailExists = $this->userModel->isMailExists($input['mail'], $isNew ? null : $input['user_id']);
            if ($isMailExists) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.err_mail_exists'), 3);
                return Lib::returnJsonResult(false, __('message.err_mail_exists'));
            }

            if ($isNew) {
                unset($input['user_id']);
                $input['password'] = Hash::make(md5($input['employee_no']));
                $input['password_update_date'] = Lib::getCurrentDate();
                $input['office_id'] = $this->getUserProperty('office_id');
                $result = $this->userModel->insertData($input);
                if (empty($result)) {
                    $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.create_failed'), json_encode($input)), 3);
                    return Lib::returnJsonResult(false, __('message.create_failed'));
                }
                $userInfo = $this->userModel->getUserInfo($result->user_id);
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(true, '', $userInfo);
            }

            $where = ['user_id' => $userId];
            $errorMessage = $this->getMessageErrorMultiEdit('users', $where, $updDatetime, __('label.user'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            //update
            $result = $this->userModel->updateData($input, $input['user_id']);
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)), 3);
                return Lib::returnJsonResult(false, __('message.update_failed'));
            }

            //get data response
            $userInfo = $this->userModel->getUserInfo($input['user_id']);
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $userInfo);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
     * Check validation before save user info
     *
     * @param  \Illuminate\Http\Request     $request
     *
     * @return \Illuminate\Support\MessageBag|null
     */
    private function checkValidationUser($request)
    {
        $currentRequest = new UserRequest();
        $validator = Validator::make($request->all(), $currentRequest->userRules(), $currentRequest->userMessages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return;
    }

    /**
     * reset password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function onResetPassword(Request $request)
    {
        try {
            $this->writeLog(__METHOD__);
            $userId = $request->user_id;
            $updDatetime = $request->upd_datetime;
            if (!$userId) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' user_id', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (!$updDatetime) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' upd_datetime', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            //check multiple user update
            $where = ['user_id' => $userId];
            $errorMessage = $this->getMessageErrorMultiEdit('users', $where, $updDatetime, __('label.user'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            $userInfo = $this->userModel->getById($userId);

            $input = [
                'user_id' => $userId,
                'password' => Hash::make(md5($userInfo['employee_no'])),
                'password_update_date' => Lib::getCurrentDate(),
            ];

            //update
            $result = $this->userModel->updateData($input, $input['user_id'], false);
            if (empty($result)) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , sprintf(__('log.update_failed'), json_encode($input)), 3);
                return Lib::returnJsonResult(false, __('message.update_failed'));
            }

            //get data response
            $userInfo = $this->userModel->getUserInfo($userId);
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $userInfo);

        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
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
            $userId = $request->user_id;
            $updDatetime = $request->upd_datetime;
            if (!$userId) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_user_id'), 3);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }
            if (!$updDatetime) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , __('log.empty_param') . ' upd_datetime', 1);
                return Lib::returnJsonResult(false, __('message.err_unknown'));
            }

            //check multiple user update
            $where = ['user_id' => $userId];
            $errorMessage = $this->getMessageErrorMultiEdit('users', $where, $updDatetime, __('label.user'));
            if (!empty($errorMessage)) {
                $this->writeLog(__METHOD__ . ' [Line ' . __LINE__.']', false);
                return Lib::returnJsonResult(false, $errorMessage);
            }

            $result = $this->userModel->deleteData($userId);
            if (!$result) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.delete_failed'), $userId), 3);
                return Lib::returnJsonResult(false, __('message.delete_failed'));
            }
            return Lib::returnJsonResult(true, "", true);
        } catch (Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }
}
