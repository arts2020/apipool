<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="UserTrade"))
 */
class UserTrade extends Model
{

    protected $table = 'pool_user_trading_record';

    protected $primarykey = 'id';

    protected $fillable = [
        'userid',
        'asset',
        'amount',
        'type',
        'from_address',
        'to_address',
        'tx',
        'state'
    ];

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
     * 限制查找交易完成的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeState($query,$state)
    {
        return $query->where('state',$state);
    }


    /**
     * 动态格式化时间戳
     */
    public function getCreatedAtAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['created_at'])->toDateString();
    }

}