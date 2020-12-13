<?php
/**
 * 优惠券
 * @author: alan
 * @Date: 17/5/2
 */
namespace App\Models;

use Carbon\Carbon;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Ticket"))
 */
class AppLog extends Base {

    protected $table = 'app_log';
    protected $primarykey = 'id';

}