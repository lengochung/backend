<?php

namespace App\Modules\Common\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Utils\Lib;

abstract class BaseModel extends Model
{
    protected $ASC = 'asc';
    protected $DESC = 'desc';
    /**
     * Create a new BaseModel instance.
     *
     * @return void
     */
    public function __construct(array $attributes = [])
    {

        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */
        $fillable = [
            'add_datetime',
            'upd_datetime',
            'add_user_id',
            'upd_user_id'
        ];

        /**
         * The attributes that should be cast to native types.
         *
         * @var array
         */
        $casts = [
            'add_datetime' => 'datetime:'.DATE_TIME_FORMAT,
            'upd_datetime' => 'datetime:'.DATE_TIME_FORMAT
        ];

        $this->fillable = array_merge($fillable, $this->fillable);
        $this->casts = array_merge($casts, $this->casts);
        parent::__construct($attributes);
    }


    /**
     * Indicates if the model should be timestamped.
     * Do not use the default field is created_at and updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Detach models from the relationship.
     *
     * @return void
     */
    public static function boot()
    {
        $userLogin = BaseModel::getUserLogin();
        $userId = 0;
        if ($userLogin) {
            $userId = $userLogin -> user_id;
        }
        parent::boot();
        static::creating(function ($model) use($userId) {
            $model->add_user_id = $userId;
            $model->add_datetime = Lib::toMySqlNow();
            $model->upd_user_id = $userId;
            $model->upd_datetime = Lib::toMySqlNow();
        });
        static::updating(function ($model) use($userId) {
            $model->upd_user_id = $userId;
            $model->upd_datetime = Lib::toMySqlNow();
        });
    }

    /**
     * Get user login
     *
     * @return object|null
     */
    public static function getUserLogin()
    {
        $user = Auth::guard(AUTH_API_GUARD_KEY)->user();
        return !empty($user) ? $user : null;
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
     * Laravel does not support update by condition composite primary keys
     * Handle and set info update for $query params before create
     * @param object|array $data
     * @param isArray boolean
     * @param isUpdate boolean
     * @param isDelete boolean
     * @return object|array $data after set create/update information
     */
    protected function assignData($data = [], $isArray = false, $isUpdate = false, $isDelete = false) {
        try {
            $userLogin = BaseModel::getUserLogin();
            $userId = 0;
            if ($userLogin) {
                $userId = $userLogin -> user_id;
            }
            /**
             * Insert
             */
            if(!$isUpdate) {
                if(!$isArray) {
                    return array_merge($data, [
                        'add_user_id' => $userId,
                        'add_datetime' => Lib::toMySqlNow(),
                        'upd_user_id' => $userId,
                        'upd_datetime' => Lib::toMySqlNow(),
                        'is_deleted' => $isDelete ? 1 : 0
                    ]);
                }

                $dataBatch = [];
                foreach($data as $record) {
                    array_push($dataBatch, array_merge($record, [
                        'add_user_id' => $userId,
                        'add_datetime' => Lib::toMySqlNow(),
                        'upd_user_id' => $userId,
                        'upd_datetime' => Lib::toMySqlNow(),
                        'is_deleted' => $isDelete ? 1 : 0
                    ]));
                }
                return $dataBatch;
            }

            /**
             * Update
             */
            if($isUpdate) {
                if(!$isArray) {
                    return array_merge($data, [
                        'upd_user_id' => $userId,
                        'upd_datetime' => Lib::toMySqlNow(),
                        'is_deleted' => $isDelete ? 1 : 0
                    ]);
                }

                $dataBatch = [];
                foreach($data as $record) {
                    array_push($dataBatch, array_merge($record, [
                        'upd_user_id' => $userId,
                        'upd_datetime' => Lib::toMySqlNow(),
                        'is_deleted' => $isDelete ? 1 : 0
                    ]));
                }
                return $dataBatch;
            }
        } catch (\Throwable $th) {
            return null;
        }
    }
}
