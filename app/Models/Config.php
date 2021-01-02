<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Config"))
 */
class Config extends Model
{

    protected $table = 'pool_config';

    protected $primarykey = 'id';

    /**
     * 限制查找key下的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeKey($query,$val)
    {
        return $query->where('config_key', $val);
    }

}