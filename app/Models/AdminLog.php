<?php
/**
 * 用户组
 * @author: alan
 * @Date: 17/5/2
 */
namespace App\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="AdminLog"))
 */
class AdminLog extends Base {

    protected $table = 'admin_log';

    protected $primarykey = 'id';


}