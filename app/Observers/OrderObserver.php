<?php
namespace App\Observers;

use App\Models\Order;
use App\Http\Services\SmsService;
use Illuminate\Http\Request;

class OrderObserver
{

    public function __construct(Request $request, SmsService $smsservice)
    {
        $this->sms = $smsservice;
    }

    /**
     * 订单新增时：优惠券活动时优惠券状态变更
     * @param \App\Models\Order $order
     */
    public function created(Order $order)
    {

    }

    /**
     * 订单状态变化时.
     * @param \App\Models\Order $order
     */
    public function updating(Order $order)
    {

    }

}
