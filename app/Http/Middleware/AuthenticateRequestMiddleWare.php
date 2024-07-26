<?php

namespace App\Http\Middleware;
use Closure;
use Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Utils\Lib;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthenticateRequestMiddleWare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /**
         * Check request expried time
         */
        $authRequest  = $request->header('authenticate_request');
        if (empty($authRequest ) || !Lib::cryptoJsAesDecrypt($authRequest)) {
            Log::info(__METHOD__ . " No authenticate_request information found in the request headers");
            return Response::json("", 504);
        }
        $authRequest = json_decode(Lib::cryptoJsAesDecrypt($authRequest));
        if (empty($authRequest) || empty($authRequest -> expired_time)) {
            Log::info(__METHOD__ . " No authenticate_request information found in the request headers");
            return Response::json("", 504);
        }
        $expiredTime = $authRequest -> expired_time;
        $timeServerMilliseconds = Lib::getMillisecondsTimeUTC();
        $expiredTimeServerMilliseconds = Lib::convertDateUTCToMilliseconds($expiredTime);
        $convertTimeStringToMilliseconds = Lib::convertTimeStringToMilliseconds(EXPIRED_TIME_API_REQUEST);
        if ($timeServerMilliseconds - $expiredTimeServerMilliseconds > $convertTimeStringToMilliseconds) {
            Log::info(__METHOD__ . " The API request has timed out: ".$expiredTime);
            return Response::json("", 504);
        }

        // check exists role
        $user = Auth::guard(AUTH_API_GUARD_KEY)->user();
        if (!empty($user->office_id) && !empty($user->user_id)) {
            $isExistsRole = DB::table("user-roles")
            ->where([
                'office_id' => $user->office_id,
                'user_id' => $user->user_id
            ])
            ->exists();
            if (!$isExistsRole) {
                Log::info(__METHOD__ . " No permission access");
            return Response::json("", 401);
            }
        }
        return $next($request);
    }
}
