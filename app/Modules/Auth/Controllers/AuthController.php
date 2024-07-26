<?php
namespace App\Modules\Auth\Controllers;

use App\Utils\Lib;
use Illuminate\Http\Request;
use App\Modules\Models\OfficeModel;
use Illuminate\Support\Facades\Auth;
use App\Modules\Auth\Models\AuthModel;
use Illuminate\Support\Facades\Validator;
use App\Modules\Auth\Requests\AuthRequest;
use App\Modules\Common\Controllers\BaseController;

class AuthController extends BaseController
{
    /**
     * The AuthModel instance.
     */
    protected $authModel;
    /**
     * The OfficeModel instance.
     */
    protected $officeModel;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(
        AuthModel $authModel,
        OfficeModel $officeModel,
    ){
        parent::__construct();
        $this->authModel = $authModel;
        $this->officeModel = $officeModel;
    }

    /**
    * Authenticate a user and generate a JWT token for login.
    *
    *
    * @param Illuminate\Http\Request $request The login request data.
    * @return Illuminate\Http\JsonResponse The JSON response containing the authentication result and user information.
    */
    public function userLogin(Request $request){
        try {
            $this->writeLog(__METHOD__);
            $credentials = [
                'mail' => $request->email,
                'password' => $request->password
            ];
            $checkValidate = $this -> checkValidate($request);
            if ($checkValidate != null) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' . ' checkValidate', json_encode($request->all()) , 3);
                return Lib::returnJsonResult(false, $checkValidate);
            }
            //If you remember the password, set the token for 1 year
            $rememberPassword = $request->remember_password;
            if (!empty($rememberPassword)) {
                config()->set('jwt.ttl', 24*60*360);
            }
            $token = Auth::guard(AUTH_API_GUARD_KEY)->attempt($credentials);
            if (!$token) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.login_failed'), $request->email), 3);
                return Lib::returnJsonResult(false, __('message.login_failed'));
            }
            $role = $this->getRole();
            if (!$role) {
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.login_no_permission'), $request->email), 3);
                return Lib::returnJsonResult(false, __('message.login_no_permission'));
            }

            $user =  $this->guard()->user();
            $resUser['user_id'] = $user->user_id;
            $resUser['office_id'] = $user->office_id;
            $resUser['user_first_name'] = $user->user_first_name;
            $resUser['user_last_name'] = $user->user_last_name;
            $resUser['mail'] = $user->mail;
            $resUser['access_token'] = $token;
            $office = $this->officeModel->getById($resUser['office_id']);
            if ($office) {
                $resUser['group_office_id'] = $office->group_office_id;
            }
            $this->writeLogLevel(__METHOD__, sprintf(__('log.login_successful'), $request -> ip(), $request->email) , 1);
            return Lib::returnJsonResult(true, '', $resUser);
        } catch (\Exception $e) {
            $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']' , $e->getMessage(), 3);
            return Lib::returnJsonResult(false, __('message.err_unknown'));
        }
    }

    /**
    * Check if the request data is valid.
    *
    *
    * @param Illuminate\Http\Request $request The request data to validate.
    * @return Illuminate\Contracts\Validation\Validator|null The validation errors if the request data is invalid, otherwise null.
    */
    private function checkValidate ($request) {
        $currentRequest = new AuthRequest();
        $validator = Validator::make($request->all(), $currentRequest->rules(), $currentRequest->messages());
        if ($validator->fails()) {
            return $validator->errors();
        }
        return null;
    }

    /**
    * Logout the authenticated user.
    *
    * @return Illuminate\Http\JsonResponse The JSON response indicating the result of the logout operation.
    */
    public function userLogout()
    {
        try {
            $this->writeLog(__METHOD__);
            if(!empty($this->guard()->user())) {
                $this->guard()->logout();
            }
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true);
        } catch (\Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return Lib::returnJsonResult(false);
        }
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userRefreshToken()
    {
        try {
            $this->writeLog(__METHOD__);
            $refreshToken = $this->respondWithToken($this->guard()->refresh());
            if (empty($refreshToken) || empty($refreshToken -> original)) {
                $this->writeLog(__METHOD__, false);
                return Lib::returnJsonResult(false);
            }
            //$expires_in = $refreshToken -> original['expires_in'];
            $refreshToken = $refreshToken -> original['access_token'];
            $this->writeLog(__METHOD__, false);
            return Lib::returnJsonResult(true, '', $refreshToken);
        } catch (\Exception $e) {
            $this->writeLogLevel(__METHOD__, $e->getMessage(), 3);
            return Lib::returnJsonResult(false);
        }
    }
    /**
    * Get the token array structure.
    * @param string $token
    * @return \Illuminate\Http\JsonResponse
    */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => $this->guard()->user(),
            'expires_in' => $this->guard()->factory()->getTTL() * 60
        ]);
    }
    /**
    * Get the guard to be used during authentication
    * @return \Illuminate\Contracts\Auth\Guard
    */
    public function guard()
    {
        return Auth::guard(AUTH_API_GUARD_KEY);
    }
}
