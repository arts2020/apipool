<?php
/**
 * 后台管理员
 * User: alan
 * Date: 17/5/2
 * Time: 上午11:26
 */
namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Admin"))
 */
class Admin extends Base implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    protected $table = 'admin';

    protected $primarykey = 'id';

    public $mp_status = [
        1 => '正常',
        2 => '禁用'
    ];

    public $mp_position =[
        1 => '主管',
        2 => '客服',
    ];

    protected $fillable = [
        'nickname',
        'username',
        'password',
        'email',
        'phone',
        'status',
        'is_del'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function rolemp(){
        return $this->hasOne('\App\Models\RoleMp','member_id','id');
    }

}