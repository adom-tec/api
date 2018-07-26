<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $table = 'cfg.Entities';
    protected $primaryKey = 'EntityId';

    protected $fillable = ['Nit', 'BusinessName', 'Code', 'Name'];

    public $timestamps = false;
}
