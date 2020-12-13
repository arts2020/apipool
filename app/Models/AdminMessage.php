<?php
/**
 * 管理员站内信
 * @author: alan
 * @Date: 17/5/2
 */
namespace App\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="AdminMessage"))
 */
class AdminMessage extends Base {

    protected $table = 'admin_message';

    protected $primarykey = 'id';

    public $mp_is_deal = [
        0   => '未处理',
        1   => '已处理',
    ];
}