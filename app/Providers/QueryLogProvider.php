<?php
namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;

class QueryLogProvider extends ServiceProvider
{
    // Define the log channel for database queries
    private $_channel = 'querylog';
    /**
     * The URIs that should be excluded.
     *
     * @var array
     */
    protected $except = [
        "debug-logs",
        "debug-logs/*",
        "_debugbar",
        "_debugbar/*",
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Store the channel in a variable for use in closures
        $channel = $this->_channel;
        $request = request();
        // Check if the log channel is enabled in the configuration
        if (!config("logging.channels.{$channel}.enable") || $this->inExceptArray($request)) {
            return;
        }
        $requestId = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 8);
        $header = json_encode($this->getRequestHeaders());
        Log::channel($channel)->debug(
            '[REQUEST_ID: ' . $requestId . '] IP: ' . $this->getUserIpAddr() . ' - Header (Json):  ' . $header . ' '
        );
        Log::channel($channel)->debug(
            '[REQUEST_ID: ' . $requestId . '] Url Request [' . $request->method() . ']: '
            . ($request->fullUrl() ?? "UNKNOWN")
        );
        DB::listen(function ($query) use ($requestId, $channel) {
            $tblLoginQuery = '/select \* from `users` where `id` = \'\d+\' limit/i';
            $executionTime = $query->time;
            $slowQueryTime = config("logging.channels.{$channel}.slow_query_time")*1;
            // Determine log level based on execution time
            $logLevel = $executionTime > $slowQueryTime ? 'warning' : 'debug';
            $buildSql = $this->buildSql($query);
            if (!preg_match($tblLoginQuery, $buildSql)) {
                Log::channel($channel)->$logLevel(
                    '[REQUEST_ID: ' . $requestId . ' | Time: ' . $query->time . '] ' .'Connection: '.$query->connectionName.', SQL: ['. $buildSql .']'
                );
            }
        });
        // Listen to the beginning of a database transaction and log it
        Event::listen(static function (TransactionBeginning $event) use ($channel) {
            Log::channel($channel)->debug('BEGIN TRANSACTION');
        });
        // Listen to the successful commit of a database transaction and log it
        Event::listen(static function (TransactionCommitted $event) use ($channel) {
            Log::channel($channel)->debug('COMMIT');
        });
        // Listen to the rollback of a database transaction and log it
        Event::listen(static function (TransactionRolledBack $event) use ($channel) {
            Log::channel($channel)->debug('ROLLBACK');
        });
    }

    /**
     * buildSql
     *
     * @param  mixed $query
     * @return string
     */
    private function buildSql($query)
    {
        $sql = str_replace('%', '[//]', $query->sql);
        $sql = str_replace('?', '%s', str_replace('?', "'?'", $sql));
        $sql = vsprintf($sql, $query->bindings);
        $sql = str_replace('[//]', '%', $sql);

        return $sql;
    }

    /**
     * getUserIpAddr
     *
     * @return string
     */
    private function getUserIpAddr()
    {
        $ipAddress = '';
        $serverInfo = request()->server();

        if (isset($serverInfo['HTTP_CLIENT_IP'])) {
            $ipAddress = $serverInfo['HTTP_CLIENT_IP'];
        } elseif (isset($serverInfo['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $serverInfo['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($serverInfo['HTTP_X_FORWARDED'])) {
            $ipAddress = $serverInfo['HTTP_X_FORWARDED'];
        } elseif (isset($serverInfo['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $serverInfo['HTTP_FORWARDED_FOR'];
        } elseif (isset($serverInfo['HTTP_FORWARDED'])) {
            $ipAddress = $serverInfo['HTTP_FORWARDED'];
        } elseif (isset($serverInfo['REMOTE_ADDR'])) {
            $ipAddress = $serverInfo['REMOTE_ADDR'];
        } else {
            $ipAddress = 'UNKNOWN';
        }

        return $ipAddress;
    }

    /**
     * getRequestHeaders
     *
     * @return array
     */
    private function getRequestHeaders()
    {
        $headers = [];
        $serverInfo = request()->server();
        foreach ($serverInfo as $key => $value) {
            if (substr($key, 0, 5) != 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }

        return $headers;
    }

    /**
     * Determine if the request has a URI that should pass through Query Log.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }
            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
