<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Notice"))
 */
class Notify extends Model
{

    protected $table = 'pool_notify';

    protected $primarykey = 'id';

    /**
     * 限制查找未过期的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpireAt($query)
    {
        return $query->where('expire_at','>=', now());
    }

    /**
     * 动态格式化时间戳
     */
    public function getCreatedAtAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['created_at'])->toDateString();
    }

}