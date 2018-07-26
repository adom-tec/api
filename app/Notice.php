<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $table = 'cfg.Notice';
    protected $primaryKey = 'NoticeId';
    protected $fillable = ['NoticeTitle', 'NoticeText'];

    const CREATED_AT = 'CreationDate';
    const UPDATED_AT = null;

    public function user()
    {
        return $this->belongsTo('App\User', 'UserId');
    }
}
