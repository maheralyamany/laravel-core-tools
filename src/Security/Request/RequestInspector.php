<?php

namespace Maher\CoreTools\Security\Request;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maher\CoreTools\Security\Audit\LogAuditLogger;
use Maher\CoreTools\Security\Helpers\SecurityHelper;

class RequestInspector extends BaseRequestManager
{
    
    protected $patterns = [
        // SQL / file read / stacked queries
        '/\bload_file\s*\(/i',
        '/\bunion\s+select\b/i',
        '/\bdeclare\s+@/i',        // MSSQL variable declaration
        '/\border\s+by\b/i',
        '/\bdrop\s+table\b/i',
        '/\binsert\s+into\b/i',
        '/\bupdate\s+\w+\s+set\b/i',
        '/\bselect\b.*\bfrom\b/i',
        '/(;|\b--\b|\b#\b).*(\bselect\b|\bdrop\b)/i', // stacked queries / comments
        '/\b(exec|system|shell_exec|popen|proc_open|passthru|Runtime\.getRuntime|java\.lang\.Runtime)\b/i',
        '/(\/etc\/passwd)/i',
        '/<\?php/i',
        '/(curl|wget)\s+http/i',
        '/base64_decode\(/i',
        '/(eval\(|new\s+java\.util\.Scanner)/i',
        // Command injection / shell
        '/\b(exec|system|shell_exec|popen|proc_open|passthru)\b/i',
        //'/[`|&&|\|\||;]\s*[a-z0-9_\/-]+/i', // shell chaining `;` `&&` `||` `|`
        '/\b(nslookup|dig|curl|wget|powershell|Invoke-Expression|Invoke-WebRequest)\b/i',

        // Code execution
        '/\beval\s*\(/i',
        '/\bcompile\s*\(/i',
        '/\bexec\(/i',
        '/\bpython\s+-c\b/i',

        // Functions often used by attackers
        '/\bbenchmark\s*\(/i',
        '/\bsleep\s*\(/i',
        '/\b(auto_prepend_file|allow_url_include|index\.php|system\.php|\.php)/i',

        // DNS exfiltration pattern common flags
        //'/\b-q=|--type=|@/i'
    ];
    public function hasSuspiciousPayload(Request $r): bool
    {
        return str_contains(json_encode($r->all()), '<script');
    }


    // Option: IPs whitelist or exceptions
    protected $whitelistIps = [
        '127.0.0.1',
        "::1"
    ];
    public  function checkMaliciousRequest(): bool
    {
        //
        try {
            $manager=$this->getReqManager();
            $request = $manager->getRequest();
            $isLivewire = $manager->isLivewireRequest($request);
            //if (/* \in_array($ip, $this->whitelistIps) || */isLivewireRequest($request))
            //	return false;

            $body = (string) $request->getContent();
            if ($isLivewire) {
                if (str_contains($body, '_token') && !$manager->isGuest())
                    return false;
            }
            $ip = $manager->getIp();
            // Gather data to check
            $fullUrl = $manager->getFullUrl();
            $uri = $request->getRequestUri();
            $queryString = $manager->getQueryString() ?? '';
            $method = $request->method();
            $userAgent = $manager->getUserAgent();
            $headers = json_encode($request->headers->all());


            // Try to decode common encodings (urlencoded and base64)
            $decodedQuery = urldecode($queryString);
            $decodedBody = urldecode($body);
            $maybeBase64 = $manager->maybeBase64($body) ? base64_decode($body) : '';
            $p = $request->all();
            $params = \array_merge($p, [
                $fullUrl,
                $uri,
                $queryString,
                $decodedQuery,
                $decodedBody,
                $maybeBase64,
                $headers
            ]);


            $params[] = $body;

            $haystack = implode("\n", $params);

            // Normalize whitespace and lower-case for some checks (regex use i flag though)
            foreach ($this->patterns as $pattern) {
                if ($haystack && preg_match($pattern, $haystack)) {

                    try {
                        $location = $manager->getLocationFromIp($ip);
                        // Create a security log entry
                        //$userAgent=Str::limit($userAgent, 1024);
                        $logData = [
                            'ip' => $ip,
                            'url' => Str::limit($fullUrl, 2048),
                            'method' => $method,
                            'pattern' => $pattern,
                            'user_agent' => $userAgent,
                            'body_sample' => Str::limit($body, 2000),
                            'location' => $location,
                            'time' => now()->toDateTimeString(),

                        ];
                        if (!$isLivewire && !\in_array($ip, $this->whitelistIps))
                            SecurityHelper::blockIp($ip, $userAgent, $manager->getUserId());
                        //SecurityLog::create($logData);
                        /* $this->notifyByEmail([ 'ip' => $ip, 'url' => $fullUrl, 'method' => $method, 'pattern' => $pattern, 'user_agent' => $userAgent, 'location' => \json_array($location), ]); */
                        // Additional: detailed log for forensic analysis
                        LogAuditLogger::warning('Malicious URL detected', $logData);
                    } catch (\Throwable $th) {
                        report($th);
                        //throw $th;
                    }

                    return true;
                }
            }
        } catch (\Throwable $th) {
            report($th);
            //throw $th;
        }
        return false;
    }
    protected function notifyByEmail(array $data)
    {

        /* $to = config('security.alert_email', config('mail.from.address'));
		Mail::raw(
			"⚠️ Malicious Request Detected:\n\n" .
				"IP: {$request->ip()}\n" .
				"URL: {$request->fullUrl()}\n" .
				"Agent: {$request->userAgent()}\n" .
				"Pattern: {$pattern}\n" .
				"Time: " . now()->toDateTimeString(),
			function ($message) use ($to) {
				$message->to($to)
					->subject('⚠️ Laravel Security Alert - Injection Attempt Detected');
			}
		); */
        // إرسال البريد
        try {
            //Mail::to('admin@yourapp.com')->send(new SecurityAlertMail($data));
        } catch (\Exception $e) {
            Log::error('Security alert email failed to send', ['error' => $e->getMessage()]);
        }
    }
    public function isIpBlocked(): bool
    {
        return SecurityHelper::isIpBlocked($$this->getReqManager()->getIp());
    }
}
