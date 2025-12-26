<?php

use Illuminate\Support\Facades\Cache;

if (!function_exists('internet_available')) {

    /**
     * Check internet availability using DNS + HTTP
     *
     * @param int $cacheSeconds
     * @return bool
     */
    function internet_available(int $cacheSeconds = 30): bool
    {
        return Cache::remember(
            'internet_available_status',
            now()->addSeconds($cacheSeconds),
            function () {
                return check_dns_connection() && check_http_connection();
            }
        );
    }
}
if (!function_exists('check_dns_connection')) {

    function check_dns_connection(int $timeout = 2): bool
    {
        try {
            $connection = @fsockopen('8.8.8.8', 53, $errno, $errstr, $timeout);
            if ($connection) {
                fclose($connection);
                return true;
            }
        } catch (\Throwable) {
            // ignore
        }

        return false;
    }
}
if (!function_exists('check_http_connection')) {

    function check_http_connection(int $timeout = 3): bool
    {
        try {
            $ch = curl_init('https://www.google.com');

            curl_setopt_array($ch, [
                CURLOPT_NOBODY         => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
            ]);

            curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $status >= 200 && $status < 400;
        } catch (\Throwable) {
            return false;
        }
    }
}
