<?php
namespace App\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Role"))
 */
class Role extends Base {

    protected $table = 'sys_admin_role';

    protected $primarykey = 'id';

}