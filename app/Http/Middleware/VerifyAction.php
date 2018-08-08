<?php

namespace App\Http\Middleware;

use App\ActionResource;
use App\RoleActionResource;
use App\UserRole;
use Closure;

class VerifyAction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $action)
    {
        $actionResourceId = ActionResource::where('Route', $action)->first();
        if ($actionResourceId) {
            $actionResourceId = $actionResourceId->ActionResourceId;
            $roles = UserRole::where('UserId', $request->user()->UserId)->pluck('RoleId');
            $rolesActionResource = RoleActionResource::whereIn('RoleId', $roles)
                ->where('ActionResourceId', $actionResourceId)
                ->first();

            if ($rolesActionResource) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'No tiene permisos para realizar esta operación'
        ], 403);

    }
}
