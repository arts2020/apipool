<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="LoginLog"))
 */
class LoginLog extends Model {

    protected $table = 'pool_user_login';

    protected $primarykey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'username',
        'nickname',
        'state',
        'ip',
        'logintype',
        'devtype',
        'desc'
    ];


}