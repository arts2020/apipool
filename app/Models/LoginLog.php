<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="SysSmsLog"))
 */
class LoginLog extends Model {

    protected $table = 'pool_user_log';

    protected $primarykey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'ip',
        'login_at',
        'remark'
    ];


}