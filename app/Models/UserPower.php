<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Notice"))
 */
class UserPower extends Model
{

    protected $table = 'pool_user_power';

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
     * 限制查找对应状态的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeState($query,$state)
    {
        return $query->where('state', '=', $state);
    }

    /**
     * 动态格式化时间戳
     */
    public function getCreatedAtAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['createtime'])->toDateString();
    }

    /**
     * 获取算力对应的订单数据
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(order::class, 'orderid', 'id');
    }
}