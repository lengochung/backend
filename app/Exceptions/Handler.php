<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use \Tymon\JWTAuth\Exceptions\JWTException;
use \Tymon\JWTAuth\Exceptions\TokenExpiredException;
use \Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'new_password',
        'password',
        'confirm_new_password',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // $this->reportable(function (Throwable $e) {
        //     //
        // });
        $this->renderable(function(TokenInvalidException $e, $request){
            return Response::json("", 401);
            //return Response::json(['error'=>'Invalid token'], 401);
        });
        $this->renderable(function (TokenExpiredException $e, $request) {
            return Response::json("", 401);
            //return Response::json(['error'=>'Token has Expired'], 401);
        });
        $this->renderable(function (JWTException $e, $request) {
            return Response::json("", 401);
            //return Response::json(['error'=>'Token not parsed'], 401);
        });
    }
}
