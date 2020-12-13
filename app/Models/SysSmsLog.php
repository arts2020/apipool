<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="SysSmsLog"))
 */
class SysSmsLog extends Model {

    protected $table = 'sys_sms_log';

    protected $primarykey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'sms_type',
        'phone',
        'verify_code',
        'return_data',
        'value_type',
        'send_at',
        'return_at'
    ];


}