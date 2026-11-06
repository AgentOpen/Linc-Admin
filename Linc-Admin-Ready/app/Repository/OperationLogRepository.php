<?php

declare(strict_types=1);

namespace Linc\Repository;

use Linc\Core\Database;

/**
 * 操作日志写入：
 * dept_name 置空；location 由 IP 推导（本机 / 内网）。
 */
final class OperationLogRepository
{
    public static function record(
        string $module,
        string $action,
        ?string $requestMethod,
        ?string $requestUrl,
        ?string $requestParams,
        ?string $operator,
        ?string $ip,
        int $cost,
        int $result,
        ?string $errorMessage,
    ): void {
        Database::execute(
            "INSERT INTO sys_oper_log (module, action, request_method, request_url, request_params,
                oper_name, dept_name, ip, location, cost, result, error_msg)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $module,
                $action,
                $requestMethod,
                $requestUrl,
                $requestParams,
                $operator,
                null,
                $ip,
                location_of_ip($ip),
                $cost,
                $result,
                $errorMessage,
            ]
        );
    }
}
