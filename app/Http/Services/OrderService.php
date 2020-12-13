<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 17/8/21
 * Time: 上午9:32
 */
namespace App\Http\Services;

use App\Models\Order;
use App\Models\OrderHistory;

class OrderService {

    public $model ;
    public function __construct()
    {
        $this->model = new Order();
    }

    /**
     * 获取订单按钮文字
     */
    public function getOrderButtonText( $status ){
        $text = '';
        if( $status == 5 ){
            $text = '确认订单';
        }elseif ($status == 7){
            $text = '确认出发';
        }elseif($status == 9 ){
            $text = '确认到达';
        }elseif($status == 11){
            $text = '确认完成';
        }else{
            $text = '查看详情';
        }
        return $text;
    }

}