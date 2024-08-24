<?php
namespace App\Modules\Auth\Controllers;

use App\Utils\Lib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Modules\Auth\Models\AuthModel;
use Illuminate\Support\Facades\Validator;
use App\Modules\Auth\Requests\AuthRequest;
use App\Modules\Common\Controllers\BaseController;
use App\Modules\Models\UserModel;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController {
    /**
     * The AuthModel instance.
     */
    protected $authModel;
    protected $userModel;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(
        AuthModel $authModel,
        UserModel $userModel,
    ){
        parent::__construct();
        $this->authModel = $authModel;
        $this->userModel = $userModel;
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
                'user_name' => $request->user_name,
                'password' => $request->password
            ];
            $b = Hash::make($credentials['password']);
            // var_dump(json_encode($b));
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
                $this->writeLogLevel(__METHOD__ . ' [Line ' . __LINE__.']', sprintf(__('log.login_failed'), $request->user_name), 3);
                return Lib::returnJsonResult(false, __('message.login_failed'));
            }

            $user =  $this->guard()->user();
            $resUser['user_id'] = $user->user_id;
            $resUser['head_id'] = $user->head_id;
            $resUser['full_name'] = $user->full_name;
            $resUser['email'] = $user->email;
            $resUser['access_token'] = $token;
            $this->writeLogLevel(__METHOD__, sprintf(__('log.login_successful'), $request -> ip(), $request->user_name) , 1);
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
