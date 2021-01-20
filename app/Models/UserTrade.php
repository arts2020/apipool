<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="UserTrade"))
 */
class UserTrade extends Model
{
    const TYPE_DIG  = 1;
    const TYPE_RECHARGE  = 2;
    const TYPE_TRANSFER     = 3;
    const TYPE_DRAW_COIN   = 4;
    const TYPE_DRAW_COIN_PROFIT   = 5;

    const STATE_PROCESSING    = 0;
    const STATE_SUCCESS       = 1;
    const STATE_FAIL       = 2;

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

    public static $stateMap = [
        self::STATE_PROCESSING   => '转帐中',
        self::STATE_SUCCESS    => '完成',
        self::STATE_FAIL      => '失败',
    ];

    public static $typeMap = [
        self::TYPE_DIG        => '挖矿奖励',
        self::TYPE_RECHARGE    => '充币',
        self::TYPE_TRANSFER       => '转账',
        self::TYPE_DRAW_COIN     => '提币',
        self::TYPE_DRAW_COIN_PROFIT    => '收益提币',
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
     * 限制查找交易类型的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query,$type)
    {
        return $query->where('type',$type);
    }

    /**
     * 动态格式化时间戳
     */
    public function getCreatedAtAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['created_at'])->toDateString();
    }

}