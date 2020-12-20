<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Notice"))
 */
class Notice extends Model
{

    protected $table = 'pool_notice';

    protected $primarykey = 'id';

    /**
     * 限制查找某分类的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query,$type)
    {
        return $query->where('type',$type);
    }

    /**
     * 限制查找启用的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeState($query)
    {
        return $query->where('state', '=', 1);
    }

    /**
     * 动态格式化时间戳
     */
    public function getCreatedAtAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['created_at'])->toDateString();
    }

}