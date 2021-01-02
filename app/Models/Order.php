<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Order"))
 */
class Order extends Model
{
    use SoftDeletes,Traits\UserTrait;

    protected $table = 'pool_order';
    protected $primarykey = 'id';
    protected $appends = ['expired_at'];
    protected $dates = ['deleted_at'];
    public $timestamps = true;
    public $mp_state = [
        0 => '已确认',
        1 => '已取消',
        2 => '已完成',
        3 => '已删除'
    ];

    public $mp_pay_state = [
        0 => '未支付',
        1 => '已支付',
        2 => '超时',
    ];

    public $mp_verify_state = [
        0 => '审核中',
        1 => '通过',
        2 => '未通过'
    ];

    protected $fillable = [
        'order_no',
        'asset',
        'userid',
        'total',
        'discount',
        'amount',
        'amount_cny',
        'pay_at',
        'cancel_at',
        'cancel_reason',
        'type',
        'state',
        'pay_state',
        'verify_state',
        'verify_cancel_reason',
        'verify_at',
        'created_by',
        'remark',
        'tx',
        'deleted_at'
    ];

    /**
     * 限制查找某订单编号的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderno($query,$order_id)
    {
        return $query->where('order_no',$order_id);
    }

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
     * 获取订单商品数据
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function orderProduct()
    {
        return $this->hasOne(OrderProduct::class, 'order_id', 'id');
    }

    /**
     * 待支付的过期时间
     */
    public function getExpiredAtAttribute()
    {
        if ($this->attributes['pay_state'] == 0) {
            $config = (new Config())->Key('order_cancel')->first();
            $hour = $config ? $config->value : 2;
            return \Carbon\Carbon::parse($this->attributes['created_at'])->addHours($hour)->timestamp;
        }
        return '';
    }
}