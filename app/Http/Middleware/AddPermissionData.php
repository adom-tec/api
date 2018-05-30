<?php

namespace App\Http\Middleware;

use Closure;
use App\User;

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
            $content = json_decode($response->getContent(), true);
            if (array_key_exists('access_token', $content)) {
                $user = User::whereEmail($request->input('username'))->first();
                dd($user);
                $content['permissions'] = $user->getMenu();

            }
            return $response;
            
        }
        
        return $next($request);
    }
}
