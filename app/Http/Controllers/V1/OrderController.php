<?php

namespace App\Http\Controllers\V1;

use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\OrderProductRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class OrderController extends ApiController
{
    protected $orderRep;
    protected $productRep;
    protected $orderProductRep;

    public function __construct(Request $request, OrderRepository $orderRepository,
                                ProductRepository $productRepository,
                                OrderProductRepository $orderProductRepository)
    {
        parent::__construct($request);
        $this->orderRep = $orderRepository;
        $this->productRep = $productRepository;
        $this->orderProductRep = $orderProductRepository;
    }

    /**
     * @SWG\Post(path="/addOrderInfo",
     *   tags={"addOrderInfo"},
     *   summary="添加订单",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *  @SWG\Parameter(
     *     name="product_id",
     *     in="query",
     *     description="产品Id",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Parameter(
     *     name="patient_id",
     *     in="query",
     *     description="患者Id",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Parameter(
     *     name="patient_phone",
     *     in="query",
     *     description="手机号",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="address_id",
     *     in="query",
     *     description="服务地址Id",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Parameter(
     *     name="book_time",
     *     in="query",
     *     description="服务时间",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="medical_desc",
     *     in="query",
     *     description="病情描述",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="query",
     *     description="访问唯一标记",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="增加成功", @SWG\Schema(ref="#/definitions/Order"))
     * )
     */
    public function add(Request $request)
    {
        $rules = [
            'product_id' => 'required|numeric',
            'product_nums' => 'required|numeric',
            'amount' => 'required'
        ];
        $attributes = [
            'product_id' => '产品Id',
            'product_nums' => '产品数量',
            'amount' => '支付金额'
        ];

        $validator = Validator::make($request->all(), $rules, [], $attributes);
        $messages = $validator->messages();
        if ($validator->failed()) {
            foreach ($messages->all() as $message) {
                return $this->fail(100, $message);
            }
        }

        //获取产品信息
        $productInfo = $this->productRep->getById($request->product_id);

        $total_fee = $productInfo->price * $request->product_nums;

        if ($request->product_nums > $productInfo->surplus) {
            return $this->fail(100, '份额不足，请核实');
        }

        if (!floatcmp($request->amount, $total_fee)) {
            return $this->fail(100, '订单金额有误，请核实');
        }
        $insert = [
            'order_no'  => build_no('T'),
            'userid'    => $this->user_id,
            'total'     => $request->amount,
            'amount'    => $request->amount,
            'state'     => 0,
            'pay_state' => 0,
        ];

        $orderInfo = $this->orderRep->store($insert);

        //产品减份额
        $this->productRep->decrement($request->product_id,'surplus',$request->product_nums);

        //插入订单产品快照信息
        $orderProduct = [
            'order_id' => $orderInfo->id,
            'product_id' => $productInfo->id,
            'product_name' => $productInfo->name,
            'number' => $productInfo->number,
            'asset' => $productInfo->asset,
            'price' => $productInfo->price,
            'discount' => $productInfo->discount,
            'fee' => $productInfo->fee,
            'days' => $productInfo->days,
            'imgurl' => $productInfo->imgurl,
            'imgurl2' => $productInfo->imgurl2,
            'desc' => $productInfo->desc,
            'begintime' => $productInfo->begintime,
            'endtime' => Carbon::parse($productInfo->begintime)->addDays($productInfo->days)->toDateTimeString(),
            'tag' => $productInfo->name,
            'product_count' => $request->product_nums,
            'total_price'=> $request->amount
        ];
        $this->orderProductRep->store($orderProduct);

        return $this->success($orderInfo);
    }

    /**
     * @SWG\Get(path="/getOrderByUid?page={page}&pageRows={pageRows}&token={token}",
     *   tags={"ogetOrderByUid"},
     *   summary="根据订单状态获取订单",
     *   description="",
     *   operationId="getOrders",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="page",
     *     in="path",
     *     description="页码",
     *     type="integer",
     *     required=true,
     *     default=1
     *   ),
     *   @SWG\Parameter(
     *     name="pageRows",
     *     in="path",
     *     description="每页条数",
     *     type="integer",
     *     required=true,
     *     default=15
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="path",
     *     description="token访问唯一凭证",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Order"))
     *
     * )
     */
    public function getOrders(Request $request)
    {
        $result = $this->orderRep->getOrderLists($this->user_id, $request->pageRows);
        $result->appends([
            'pageRows' => $request->pageRows,
        ]);
        return $this->success($result);
    }

    /**
     * @SWG\GET(path="/getOrderById?order_id={order_id}&token={token}",
     *   tags={"getOrderById"},
     *   summary="根据订单编号获取订单信息",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="order_id",
     *     in="query",
     *     description="订单id",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="path",
     *     description="token访问唯一凭证",
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Order"))
     * )
     */
    public function getOrderInfo(Request $request)
    {
        $order_id = $request->input('order_id');
        if (!$order_id) {
            return $this->fail(100, '缺少参数订单编号');
        }

        $order = $this->orderRep->getOrderDetail($order_id);

        if (empty($order)) {
            return $this->fail(101, '订单不存在');
        } else {
            return $this->success($order);
        }
    }

}