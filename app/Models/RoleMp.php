<?php
/**
 * 用户组
 * @author: alan
 * @Date: 17/5/2
 */
namespace App\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="RoleMap"))
 */
class RoleMp extends Base {

    protected $table = 'admin_role_mp';

    protected $primarykey = 'id';

    public function role(){
        return $this->hasOne('\App\Models\Role','id','role_id');
    }
}