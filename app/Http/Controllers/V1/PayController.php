<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use App\Models\ProductSku;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\OrderProductRepository;
use App\Repositories\UserAssetRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PayController extends ApiController
{
    protected $orderRep;
    protected $productRep;
    protected $orderProductRep;
    protected $assetRep;

    public function __construct(Request $request, OrderRepository $orderRepository,
                                ProductRepository $productRepository,
                                OrderProductRepository $orderProductRepository,
                                UserAssetRepository $assetRepository)
    {
        parent::__construct($request);
        $this->orderRep = $orderRepository;
        $this->productRep = $productRepository;
        $this->orderProductRep = $orderProductRepository;
        $this->assetRep = $assetRepository;
    }

    /**
     * @SWG\GET(path="/payment?order_id={order_id}&token={token}",
     *   tags={"payment"},
     *   summary="支付接口",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="order_id",
     *     in="query",
     *     description="订单主键ID",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="path",
     *     description="token访问唯一凭证",
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Nurse"))
     * )
     */
    public function payment(Request $request)
    {
        $order_id = $request->input('order_id');

        $tx = $request->input('tx');

        if (intval($order_id) == 0) {
            return $this->apiReturn(['code' => 100, 'msg' => '订单ID不存在']);
        }
        $orderInfo = $this->orderRep->getById($order_id);
        if ($orderInfo->pay_at > 0) {
            return $this->apiReturn(['code' => 100, 'msg' => '该笔订单已支付']);
        }

        if (!$tx) {
            return $this->apiReturn(['code' => 100, 'msg' => '交易HASH不能为空']);
        }

        //获取对应类型的钱包可用余额
        $assetInfo = $this->assetRep->getAssetInfo($this->user_id,$orderInfo->asset);

        if($assetInfo){
            $update = [
                'pay_at' => now()->toDateTimeString(),
                'state' => 2,
                'pay_state' => 1,
                'tx' => $tx,
            ];
            $res = $this->orderRep->savePost($orderInfo,$update);
            return $this->success($res, '支付成功');

        }else{
            return $this->apiReturn(['code' => 100, 'msg' => '数据有误，请核实！']);
        }
    }
}