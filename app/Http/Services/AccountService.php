<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 2017/8/30
 * Time: 上午10:04
 */

namespace App\Http\Services;

use App\Models\Account;
use App\Models\AccountLog;
use App\Models\ProductPayment;

class AccountService {
    public $model ;
    public function __construct()
    {
        $this->model = new Account();
    }

    /**
     * @author alan
     * @param $type_id
     * @param int $type
     * @param $amount
     * @return array
     */
    public function add($order_id, $type_id, $type =2, $amount, $product_id = 0)
    {
        if ($type == 0) {
            return ['code'=>100,'msg'=>'缺少Type'];
        }
        if ($type_id == 0) {
            return ['code'=>100,'msg'=>'缺少TypeId'];
        }
        $where = [];
        $where['type'] = $type;
        $where['type_id'] = $type_id;
        $accountInfo = $this->model->selectOne($where);
        $myAmount = 0 ;
        if (!empty($accountInfo)) {
            $myAmount = $accountInfo['amount'] + $amount ;
            $this->model->where('id',$accountInfo['id'])->update(['amount'=> $myAmount,'updated_at'=>time()]);
            $id = $accountInfo['id'];
        } else {
            $this->model->type = $type;
            $this->model->type_id = $type_id ;
            $this->model->amount = $amount ;
            $this->model->created_at = time();
            $this->model->save();
            $id = $this->model->id;
            $myAmount = $amount ;
        }
        $accountLogModel = new AccountLog();
        $accountLogModel->order_id = $order_id;
        $accountLogModel->account_id = $id;
        $accountLogModel->consume_fee = $amount;
        $accountLogModel->consume_content = '接单入账';
        $accountLogModel->consume_type = 4;
        $accountLogModel->created_at = time();
        $accountLogModel->save();

        //增加奖励金
        if($product_id > 0){
            $productPaymentModel = new ProductPayment();
            $payment = $productPaymentModel->selectOne(['product_id'=>$product_id],['id'=>'desc']);
            if($payment['award_fee'] > 0){
                $this->model->where('id',$id)->update(['amount'=> $myAmount + $payment['award_fee']]);
                $accountLogModel = new AccountLog();
                $accountLogModel->order_id = $order_id;
                $accountLogModel->account_id = $id;
                $accountLogModel->consume_fee = $payment['award_fee'];
                $accountLogModel->consume_content = '服务奖励金';
                $accountLogModel->consume_type = 4;
                $accountLogModel->created_at = time();
                $accountLogModel->save();
            }
        }
        return ['code'=>200,'msg'=>'操作成功'];
    }
}