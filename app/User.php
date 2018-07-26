<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\UserRole;
use App\RoleActionResource;
use App\ActionResource;

class User extends Authenticatable
{
    
    use HasApiTokens, Notifiable;

    protected $table = 'sec.Users';
    protected $primaryKey = 'UserId';
    protected $fillable = ["FirstName", "SecondName","Surname","SecondSurname", "Email", "State", "Password"];

    public $timestamps = false;
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'Password'
    ];
    public function findForPassport($username) {
        return $this->whereEmail($username)->first();
    }

    public function getAuthPassword()
    {
        return $this->Password;
    }

    public function getMenu()
    {
        $orderModule = [];
        $orderResource = [];
        $roles = UserRole::where('UserId', $this->UserId)->pluck('RoleId');
        $rolesActionsResources = RoleActionResource::whereIn('RoleId', $roles)->pluck('ActionResourceId');
        $actionsResources = ActionResource::whereIn('ActionResourceId', $rolesActionsResources)
            ->with(['resource.module' => function($query) {
                $query->orderBy('Modules.Order');
            }])
            ->with('action')
            ->get()
            ->map(function($actionResource) {
                return [
                    'ResourceId' => $actionResource->ResourceId,
                    'ResourceName' => $actionResource->resource->Name,
                    'ModuleId' => $actionResource->resource->module->ModuleId,
                    'ModuleName' => $actionResource->resource->module->Name,
                    'ActionName' => $actionResource->action->Name,
                    'Route' => $actionResource->Route,
                    'RouteFrontEnd' => $actionResource->resource->RouteFrontEnd,
                    'VisibleResource' => (bool) $actionResource->resource->Visible,
                    'OrderModule' => $actionResource->resource->module->Order,
                    'OrderResource' => $actionResource->resource->Order,
                ];
            })->toArray();

        $total = count($actionsResources);

        foreach ($actionsResources as $key => $value) {
            $orderModule[$key] = $value['OrderModule'];
            $orderResource[$key] = $value['OrderResource'];
        }
        array_multisort($orderModule, SORT_ASC, $orderResource, SORT_ASC, $actionsResources);

        $menu = [];
        for ($i = 0; count($actionsResources) > 0; $i++) {
            $menu[] = [
                'moduleId' => $actionsResources[$i]['ModuleId'],
                'moduleName' => $actionsResources[$i]['ModuleName'],
                'resources' => []
            ];
            $positionModule = count($menu) - 1;

            for ($j = $i; count($actionsResources) > 0; $j++) {
                if ($menu[$positionModule]['moduleId'] == $actionsResources[$j]['ModuleId']) {
                    $menu[$positionModule]['resources'][] = [
                        'resourceId' => $actionsResources[$j]['ResourceId'],
                        'resourceName' => $actionsResources[$j]['ResourceName'],
                        'route' => $actionsResources[$j]['RouteFrontEnd'],
                        'actions' => [],
                        'visible' => $actionsResources[$j]['VisibleResource']
                    ];
                    $positionResource = count($menu[$positionModule]['resources']) - 1;
                    for ($k = $j; count($actionsResources) > 0; $k++) {
                        if ($menu[$positionModule]['resources'][$positionResource]['resourceId'] == $actionsResources[$k]['ResourceId']) {
                            $menu[$positionModule]['resources'][$positionResource]['actions'][] = $actionsResources[$k]['ActionName'];
                            unset($actionsResources[$k]);
                            $i++;
                            $j++;
                        } else {
                            $j--;
                            break;
                        }
                    }
                } else {
                    $i--;
                    break;
                }
            }
        }
        return $menu;
    }

}
