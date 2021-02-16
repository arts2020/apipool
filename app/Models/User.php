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

    const VERIFY_STATE_UNUPLOAD  = 0;
    const VERIFY_STATE_PENDING   = 1;
    const VERIFY_STATE_PASS     = 2;
    const VERIFY_STATE_UNPASS   = 3;

    const IDENTITY_STATE_UNVERIFIED = 1;
    const IDENTITY_STATE_VERIFIED   = 2;

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
    public static $identityStateMap = [
        self::IDENTITY_STATE_UNVERIFIED   => '未认证',
        self::IDENTITY_STATE_VERIFIED   => '已认证'
    ];

    public static $verifyStateMap = [
        self::VERIFY_STATE_UNUPLOAD   => '未上传',
        self::VERIFY_STATE_PENDING    => '未审核',
        self::VERIFY_STATE_PASS       => '审核通过',
        self::VERIFY_STATE_UNPASS     => '审核不通过',
    ];


    public function getIdentityStateTextAttribute()
    {
        return self::$identityStateMap[$this->attributes['identity_state']];
    }

    public function getVerifyStateTextAttribute()
    {
        return self::$verifyStateMap[$this->attributes['verify_state']];
    }

    /**
     * 获取矿工信息
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function miner()
    {
        return $this->belongsTo(Miner::class, 'miner_id', 'id');
    }
}
