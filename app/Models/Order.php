<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Order"))
 */
class Order extends Model
{
    protected $table = 'pool_order';
    protected $primarykey = 'id';

    public $timestamps = true;

    public $mp_status = [
        0 => '已确认',
        1 => '已取消',
        2 => '已完成'
    ];

    protected $fillable = [
        'order_no',
        'userid',
        'total',
        'discount',
        'amount',
        'pay_at',
        'cancel_at',
        'cancel_reason',
        'state',
        'pay_state',
        'lat',
        'medical_desc',
        'medical_special_desc',
        'patient_phone',
        'change_book_time',
        'change_order_address',
        'sub_price',
        'cancel_reason',
        'cancel_time',
        'remark',
        'tx'
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
        return $this->hasOne(orderProduct::class, 'order_id', 'id');
    }


    /**
     * 订单数、订单金额统计
     * @param $dateArr
     * @param $hospital_id
     * @param $keshi_id
     * @return array
     */
    public function getStatistics($dateArr, $hospital_id, $keshi_id, $type = 0)
    {
        $where[] = ['created_at', '>=', $dateArr['time_start']];
        $where[] = ['created_at', '<=', $dateArr['time_end']];
        if ($hospital_id) {
            $where['hospital_id'] = $hospital_id;
        }
        if ($keshi_id || ($keshi_id == 0 && $type > 0)) {
            $where['keshi_id'] = $keshi_id;
        }

        $groupStr = 'keshi_id';
        //如果type为1，则统计为天的数据
        if ($type == 1) {
            $groupStr = 'd';
        } elseif ($type == 2) {
            $groupStr = 'w';
        }

        $where[] = ['status', '>', 1];
        $where[] = ['status', '<>', 17];
        $where[] = ['status', '<>', 19];
        $where[] = ['is_del', '=', 1];

        $list = $this->selectRaw("sum(1) as total ,sum(pay_fee) as money_total,keshi_id,FROM_UNIXTIME(created_at,'%Y-%m-%d') as d,FROM_UNIXTIME(created_at,'%Y-%u') as w")
            ->whereRaw("order_no NOT in (select parent_order_no from zpd_order GROUP BY parent_order_no)")
            ->where($where)
            ->groupBy($groupStr)
            ->get()
            ->toArray();

        if ($list) {
            $list = array_column($list, null, $groupStr);
        }
        return $list;
    }


}