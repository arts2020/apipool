<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 17/5/2
 * Time: 上午11:26
 */

namespace App\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Member"))
 */
class Member extends Base
{

    protected $table = 'pool_user';

    protected $primarykey = 'id';

    public $mp_gender = [
        1 => '男',
        2 => '女',
    ];

    public $mp_status = [
        1 => '启用',
        2 => '停用',
    ];
}