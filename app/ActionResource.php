<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActionResource extends Model
{
    protected $table = 'sec.ActionsResources';
    protected $primaryKey = 'ActionResourceId';

    public function resource()
    {
        return $this->belongsTo('App\Resource', 'ResourceId');
    }

    public function action()
    {
        return $this->belongsTo('App\Action', 'ActionId');
    }
}
