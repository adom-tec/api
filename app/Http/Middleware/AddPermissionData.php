<?php

namespace App\Http\Middleware;

use Closure;

class AddPermissionData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('post') && $request->path() == 'oauth/token' ) {
            $response = $next($request);
            dd($response);
            return $response;
        }
        
        return $next($request);
    }
}
