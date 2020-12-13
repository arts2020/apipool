<?php
/**
 * 菜单
 * @author: alan
 * @Date: 17/5/2
 */
namespace App\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Menu"))
 */
class Menu extends Base {

    protected $table = 'admin_menu';

    protected $primarykey = 'id';

    public $mp_is_hide =[
        0 => '否',
        1 => '是'
    ];


}