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
    protected $appends = ['verify_state_text','identity_state_text'];
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

    public $identity_state = [
        1 => '未认证',
        2 => '已认证',
    ];

    public $verify_state = [
        0 => '未上传',
        1 => '未审核',
        2 => '审核通过',
        3 => '审核不通过'
    ];

    public function getIdentityStateTextAttribute()
    {
        return $this->identity_state[$this->attributes['identity_state']];
    }

    public function getVerifyStateTextAttribute()
    {
        return $this->verify_state[$this->attributes['verify_state']];
    }
}
