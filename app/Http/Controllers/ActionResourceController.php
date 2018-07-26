<?php

namespace App\Http\Controllers;

use App\Role;
use App\RoleActionResource;
use Illuminate\Http\Request;
use App\ActionResource;

class ActionResourceController extends Controller
{
    public function getByModule($module)
    {
        return ActionResource::join('sec.Resources', function ($join) use ($module) {
            $join->on('sec.ActionsResources.ResourceId', '=', 'sec.Resources.ResourceId')
                ->where('sec.Resources.ModuleId', '=', $module);
        })->select('sec.ActionsResources.*')->with(['action', 'resource'])->get()
            ->map(function ($actionResource) {
                return [
                    'actionResourceId' => $actionResource->ActionResourceId,
                    'resource' => [
                        'id' => $actionResource->resource->ResourceId,
                        'name' => $actionResource->resource->Name
                    ],
                    'action' => [
                        'id' => $actionResource->action->ActionId,
                        'name' => $actionResource->action->NameLabel
                    ]
                ];
            });
    }

    public function getByRole($role)
    {
        return ActionResource::join('sec.RolesActionsResources', function ($join) use ($role) {
            $join->on('sec.ActionsResources.ActionResourceId', '=', 'sec.RolesActionsResources.ActionResourceId')
                ->where('sec.RolesActionsResources.RoleId', '=', $role);
        })->select('sec.ActionsResources.*')->with(['action', 'resource'])->get()
            ->map(function ($actionResource) {
                return [
                    'actionResourceId' => $actionResource->ActionResourceId,
                    'resource' => [
                        'id' => $actionResource->resource->ResourceId,
                        'name' => $actionResource->resource->Name
                    ],
                    'action' => [
                        'id' => $actionResource->action->ActionId,
                        'name' => $actionResource->action->NameLabel
                    ]
                ];
            });
    }

    public function store($role, Request $request)
    {
        $role = Role::findOrFail($role)->RoleId;
        $actionsResources = $request->input('actionsResources');
        \DB::beginTransaction();
        $module = $request->input('moduleId');
        try {
            RoleActionResource::join('sec.ActionsResources', 'sec.ActionsResources.ActionResourceId', '=', 'sec.RolesActionsResources.ActionResourceId')
                ->join('sec.Resources', function($join) use ($module) {
                    $join->on('sec.ActionsResources.ResourceId', '=', 'sec.Resources.ResourceId')
                        ->where('sec.Resources.ModuleId', $module);
                })
                ->where('RoleId', $role)->delete();
            if (count($actionsResources)) {
                $request->validate([
                    'actionsResources.*' => 'exists:sqlsrv.sec.ActionsResources,ActionResourceId'
                ]);

                $rolesActionsResources = [];
                foreach ($actionsResources as $actionResource) {
                    $rolesActionsResources[] = [
                        'RoleId' => $role,
                        'ActionResourceId' => $actionResource
                    ];
                }

                RoleActionResource::insert($rolesActionsResources);

            }
            \DB::commit();
            return response()->json([
                'message' => 'Los permisos del rol han sido modificados correctamente'
            ], 200);
        } catch (\Exception $exception) {
            \DB::rollback();
            return response()->json([
                'message' => 'Error, no se pudieron modificar los permisos del rol, por favor intente de nuevo'
            ], 500);
        }
    }
}
