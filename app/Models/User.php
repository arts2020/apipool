<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    protected $table = 'pool_user';

    protected $primarykey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'username',
        'phone',
        'password',
        'capital_password',
        'gender',
        'birthday',
        'id_card',
        'imgurl',
        'imgurl2',
        'user_state',
        'identity_state',
        'verify_state',
        'gender',
        'isset_capital_pwd',
        'invite_code',
        'invite_user',
        'remark',
        'profile_picture',
        'devtype',
        'devdes',
        'appversion',
        'sysinfo'
    ];

    protected $hidden = ['password', 'remember_token'];

    public $mp_gender = [
        1 => '男',
        2 => '女',
    ];

    public $mp_status = [
        1 => '启用',
        2 => '停用',
    ];
}
