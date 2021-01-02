<?php

namespace App\Http\Controllers\V1;

use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\OrderProductRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
            'nums' => 'required|numeric',
            'amount' => 'required',
        ];
        $attributes = [
            'product_id' => '产品Id',
            'nums' => '产品数量',
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
        $productInfo = $this->productRep->getById($request->product_id,'type');

        if (!$productInfo) {
            return $this->fail(100, '商品数据有误，请核实');
        }

        $total_fee = $productInfo->price * $request->nums;

        if ($request->nums > $productInfo->surplus) {
            return $this->fail(100, '份额不足，请核实');
        }

        if (!floatcmp($request->amount, $total_fee)) {
            return $this->fail(100, '订单金额有误，请核实');
        }

        // 开启一个数据库事务
        $orderInfo = DB::transaction(function () use ($request, $productInfo) {

            //创建一个订单
            $insert = [
                'order_no' => build_no('T'),
                'userid' => $this->user_id,
                'asset' => $productInfo->asset,
                'total' => $request->amount,
                'amount' => $request->amount,
                'amount_cny' => turnCny($request->amount)
            ];

            $orderInfo = $this->orderRep->store($insert);

            //插入订单产品快照信息
            $orderProduct = [
                'order_id' => $orderInfo->id,
                'product_id' => $productInfo->id,
                'product_name' => $productInfo->name,
                'number' => $productInfo->number,
                'asset' => $productInfo->asset,
                'unit'=> $productInfo->type->unit,
                'price' => $productInfo->price,
                'price_cny' => turnCny($productInfo->price),
                'discount' => $productInfo->discount,
                'fee' => $productInfo->fee,
                'days' => $productInfo->days,
                'imgurl' => $productInfo->imgurl,
                'imgurl2' => $productInfo->imgurl2,
                'desc' => $productInfo->desc,
                'begintime' => $productInfo->begintime,
                'endtime' => Carbon::parse($productInfo->begintime)->addDays($productInfo->days)->toDateTimeString(),
                'tag' => $productInfo->tag,
                'product_count' => $request->nums,
                'total_price' => $request->amount
            ];
            $this->orderProductRep->store($orderProduct);

            //产品减份额
            $this->productRep->decrement($request->product_id, 'surplus', $request->nums);

            return $orderInfo;
        });

        return $this->success($this->orderRep->getOrderDetail($orderInfo->id));
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

    /**
     * @SWG\Post(path="/order/cancel",
     *   tags={"order/cancel"},
     *   summary="用户订取消订单",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="order_id",
     *     in="query",
     *     description="订单Id",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Parameter(
     *     name="reason",
     *     in="query",
     *     description="取消原因",
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
    public function cancel(Request $request)
    {
        $rules = [
            'order_id' => 'required|numeric',
            'reason' => 'required'
        ];
        $attributes = [
            'order_id' => '订单Id',
            'reason' => '取消订单原因'
        ];

        $validator = Validator::make($request->all(), $rules, [], $attributes);
        $messages = $validator->messages();
        if ($validator->failed()) {
            foreach ($messages->all() as $message) {
                return $this->fail(100, $message);
            }
        }

        $order = $this->orderRep->getOrderDetail($request->order_id);
        if (empty($order)) {
            return $this->fail(100, '订单不存在，请核实');
        } else {
            if ($order->state == 0 && $order->pay_state == 0) {

                DB::transaction(function() use ($order,$request){
                    // 取消订单
                    $order->update([
                        'state' => 1,
                        'cancel_at'=>now()->toDateTimeString(),
                        'cancel_reason'=> $request->reason
                    ]);
                    //将订单快照中的数量加回到库存中去
                    $order->orderProduct->product->increment('surplus', $order->orderProduct->product_count);
                });

                return $this->success($order, '取消成功');
            } else {
                return $this->fail(100, '操作失败，请核实');
            }
        }
    }

    /**
     * @SWG\Post(path="/order/delete",
     *   tags={"order/delete"},
     *   summary="用户订单删除",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *  @SWG\Parameter(
     *     name="orderId",
     *     in="query",
     *     description="订单Id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="query",
     *     description="",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="删除成功", @SWG\Schema(ref="#/definitions/Order"))
     * )
     */
    public function delete(Request $request)
    {
        $order_id = $request->input('order_id');
        if (!$order_id) {
            return $this->fail(100, '缺少参数订单编号');
        }
        $order = $this->orderRep->getOrderDetail($order_id);
        if (empty($order)) {
            return $this->fail(100, '订单不存在，请核实');
        } else {
            if ($order->state == 3 && $order->deleted_at > 0) {
                return $this->fail(100, '订单已删除，请核实');
            }
            if ($order->state == 1) {
                $data['state'] = 3;
                $data['deleted_at'] = now()->toDateTimeString();
                $this->orderRep->savePost($order, $data);
                return $this->success($order, '删除成功');
            } else {
                return $this->fail(100, '操作失败，请核实');
            }
        }
    }

}