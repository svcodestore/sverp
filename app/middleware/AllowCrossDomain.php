<?php
namespace app\middleware;

use think\Response;

class AllowCrossDomain
{
    public function handle($request, \Closure $next)
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:*');
        header('Access-Control-Allow-Headers:x-requested-with,content-type,access-token,Access-Token');
        header('Access-Control-Max-Age: 1800');
        if (strtoupper($request->method()) === 'OPTIONS') {
            return Response::create()->send();
        }
        return $next($request);
    }
}
