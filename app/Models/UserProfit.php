<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="UserProfit"))
 */
class UserProfit extends Model
{

    protected $table = 'pool_user_profit';

    protected $primarykey = 'id';
    protected $appends = ['created_at'];
    /**
     * 限制查找某用户的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUserid($query,$user_id)
    {
        return $query->where('userid',$user_id);
    }

    /**
     * 限制查找某分类的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAsset($query,$type)
    {
        return $query->where('asset',$type);
    }

    /**
     * 动态格式化时间戳
     */
    public function getCreatedAtAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['createtime'])->toDateString();
    }

}