<?php

namespace App\Http\Middleware;

use Closure;

class GetColumnToReturn
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
        $keys = $request->input('keys');
        $with = $request->input('with');
        if ($keys) {
            $request->merge(['keys' => explode(',', $keys)] + ['UserId']);
        }
        if ($with) {
            $request->merge(['with' => explode(',', $with)]);
        }
        return $next($request);
    }
}
