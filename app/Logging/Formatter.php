<?php

namespace App\Logging;
use App\Utils\Lib;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Auth;
use Monolog\Formatter\LineFormatter;


class Formatter
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke(Logger $logger)
    {
        $userId = '';
        $user = Auth::guard(AUTH_API_GUARD_KEY)->user();
        if (!empty($user)){
            $userId = " [{$user -> user_id}] ";
        }
        foreach ($logger->getHandlers() as $handler) {
            $now = Lib::getCurrentDate('Y-m-d H:i:s.v');
            $formatter = new LineFormatter("[$now] [%channel%.%level_name%]$userId: %message% %context% %extra%" . PHP_EOL);
            $handler->setFormatter($formatter);
        }
    }
}
