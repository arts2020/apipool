<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 17/8/22
 * Time: 上午9:41
 */

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Services\AccountService;
use App\Http\Services\MessageService;
use App\Http\Services\OrderService;
use App\Http\Services\SmsService;
use App\Http\Services\WxService;
use App\Libs\Jiangmen;
use App\Libs\Xiaozhi;
use App\Models\GiveService;
use App\Models\AddedService;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderVisit;
use App\Models\Product;
use App\Models\OrderHaoCai;
use App\Models\ProductHaoCai;
use App\Models\ProductNurse;
use App\Models\ProductRemember;
use App\Models\ProductType;
use App\Models\Taocan;
use App\Models\Whitelist;
use App\Models\ProductSubPrice;
use App\Models\HolidayConfig;
use App\Repositories\OrderRepository;
use App\Repositories\OrderPatientRepository;
use App\Repositories\MemberPatientRepository;
use App\Repositories\ShangmenFeeRepository;
use App\Repositories\TaocanRepository;
use App\Repositories\ProductRepository;
use App\Repositories\TicketRepository;
use App\Models\ShangmenFeeDesc;
use App\Jobs\MapJob;
use App\Repositories\WhitelistRepository;
use App\Models\OrderMapTrack;
use App\Models\Blacklist;
use App\Models\Adv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\OrderDescImg;
use App\Models\OrderNurse;

class OrderController extends ApiController
{

    public $model;
    private $orderRep;

    public function __construct(Request $request, OrderRepository $orderRep, ShangmenFeeRepository $shangmenRep,
                                OrderPatientRepository $orderPatientRep, MemberPatientRepository $memberPatientRep,
                                TaocanRepository $taocanRep, ProductRepository $productRep, TicketRepository $ticketRep)
    {
        parent::__construct($request);
        $this->model = new Order();
        $this->orderRep = $orderRep;
        $this->shangmenRep = $shangmenRep;
        $this->orderPatientRep = $orderPatientRep;
        $this->memberPatientRep = $memberPatientRep;
        $this->taocanRep = $taocanRep;
        $this->ticketRep = $ticketRep;
        $this->productRep = $productRep;
    }

    /**
     * @SWG\Get(path="/order/getHomeData?token={token}",
     *   tags={"order/getHomeData"},
     *   summary="获取护士首页数据",
     *   description="",
     *   operationId="getLastOrderTime",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="token",
     *     in="path",
     *     description="token访问唯一凭证",
     *     type="integer"
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Order"))
     *
     * )
     */
    public function getHomeData(Request $request)
    {
        $return = [];
        $order = $this->model->selectRaw('*,if(`status` = 5, 1 ,2 ) as t ')
            ->where('delete_flag', '=', 2)
            ->where('is_del','=',1)
            ->where('nurse_id', '=', $this->user_id)
            ->whereIn('status', [5, 7, 9, 11])
            ->orderBy('t', 'ASC')
            ->orderBy('book_time', 'ASC')
            ->first();
        $todayCount = $this->model->where('nurse_id', '=', $this->user_id)
            ->where('is_del','=',1)
            ->whereIn('status', [5, 7, 9, 11, 13, 15])
            ->where('book_time', '<', strtotime(date("Y-m-d 23:59:59")))
            ->where('book_time', '>', strtotime(date("Y-m-d 00:00:00")))
            ->count();
        $return['today_count'] = $todayCount;

        $orderModel = new Order();
        $return['receiveTotal'] = $orderModel->getCount(['status' => 5,'is_del'=>1 ,'nurse_id' => $this->user_id]);
        $return['startTotal'] = $orderModel->where(['nurse_id' => $this->user_id,'is_del'=>1 ])->whereIn('status',[7,9])->count();
        $return['servingTotal'] = $orderModel->getCount(['status' => 11,'is_del'=>1 , 'nurse_id' => $this->user_id]);
        $return['orderTotal'] = $orderModel->where(['nurse_id' => $this->user_id,'is_del'=>1 ])->whereIn('status',[5, 7, 9, 11, 13, 15])->count();

        $messageModel = new Message();
        $where = [];
        $where['type'] = $this->user_type;
        $where['type_id'] = $this->user_id;
        $where['remind_flag'] = 0;
        $total = $messageModel->where($where)->whereIn('hospital_id', explode(',', $this->hospital_id))->count();
        $return['unReadTotal'] = $total;

        if ($order) {
            $orderService = new OrderService();
            $book_time_text = $order['book_time'];
            $return['id'] = $order['id'];
            if ($order['status'] < 7) {
                $return['book_time_text'] = '00:00';
            } else {
                if ($order['change_book_time']) { //显示修改过的服务时间
                    $return['book_time_text'] = $order['change_book_time'];
                } else {
                    $return['book_time_text'] = $book_time_text;
                }
            }
            $return['book_time'] = strtotime($order['change_book_time']?:$order['book_time']);
            $return['paidan_time'] = $order['paidan_time'];
            $return['cur_time'] = time();

            if ($order['change_order_address'] != '') { //显示修改过的服务地址
                $return['order_address'] = $order['change_order_address'];
            } else {
                $return['order_address'] = $order['order_address'];
            }
            $return['order_text'] = $this->model->mp_status[$order['status']];
            $return['status'] = $order['status'];
            $return['button_text'] = $orderService->getOrderButtonText($order['status']);

            if ($order['product_id']) {
                $productModel = new Product();
                $product = $productModel->getInfo($order['product_id']);
                $return['product_name'] = $product['product_name'];
            }
            $visitModel = new OrderVisit();
            $visit = $visitModel->selectOne(['order_id' => $order['id']]);
            $visit['order_time'] = $order['order_time'];
            $return['visit'] = $visit;
            /*2020-07-31 add by wj 增值服务*/
            $AddedService = new AddedService();
            $added_service = $order['added_service'];
            $return['select_service'] = '';
            if($added_service){
                $added_service = explode(",", $added_service);
                $select_service  = $AddedService->whereIn("id", $added_service)->get();
                $return['select_service'] = $select_service;
            }
        }
        $return['banner'] = Adv::where('is_del',1)->where('is_show',1)->get();
        $sessionData = $this->model->get($request->input('token'));
        $return['has_change'] = 0;
        if(isset($sessionData['hospitalId']))
            $return['has_change'] = 1;
        return $this->success($return);
    }

    public function nurseOrderCount(){
        $total = $this->model
            ->where('delete_flag', '=', 2)
            ->where('is_del','=',1)
            ->where('nurse_id', '=', $this->user_id)
            ->where('status','=',5)
            ->orderBy('book_time', 'ASC')
            ->count();

        return $this->success($total);

    }

    /**
     * @SWG\Get(path="/order/getOrders?status={status}&page={page}&pageRows={pageRows}&token={token}",
     *   tags={"order/getOrders"},
     *   summary="根据订单状态获取订单",
     *   description="",
     *   operationId="getOrders",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="status",
     *     in="path",
     *     description="状态 0全部 1待支付 2待服务 5待确定 7待出诊 11服务中 13待评价",
     *     type="integer"
     *   ),
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
        $result = $this->orderRep->getOrderLists($this->user_type, $this->user_id, $this->hospital_id, $request->all());
        $result->appends([
            'status' => $request->status,
            'pageRows' => $request->pageRows,
        ]);

        return $this->success($result);
    }

    /**
     * @SWG\Post(path="/order/confirmPaidan",
     *   tags={"order/confirmPaidan"},
     *   summary="派单",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="orderId",
     *     in="query",
     *     description="订单Id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="nurse_id",
     *     in="query",
     *     description="护士id",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="query",
     *     description="访问唯一凭证",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="登出成功", @SWG\Schema(ref="#/definitions/Order"))
     * )
     */
    public function confirmPaidan(Request $request)
    {
        $orderId = $request->input('orderId');
        if (!$orderId) {
            $this->fail(100, '缺少参数订单ID');
        }
        $nurseId = $request->input('nurse_id');
        if (!$nurseId) {
            $this->fail(100, '缺少参数护士ID');
        }

        $orderModel = new Order();

        $orderInfo = $orderModel->selectOne(['id' => $orderId]);

        if (empty($orderInfo)) {
            return $this->apiReturn(['code' => 100, 'msg' => '订单不存在']);
        }
        if ($orderInfo['status'] != 3) {
            return $this->apiReturn(['code' => 103, 'msg' => '订单操作有误']);
        }

        $data = [];
        $data['status'] = 5;
        $data['is_bannei'] = 0;
        $data['nurse_id'] = $nurseId;
        $data['paidan_time'] = time();
        //$orderModel->where("id","=",$orderId)->save($data);
        $this->orderRep->update($orderId,$data);


        return $this->success('','派单成功');
    }


    /**
     * @SWG\Post(path="/order/confirmJiedan",
     *   tags={"order/confirmJiedan"},
     *   summary="护士确认订单",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="orderId",
     *     in="query",
     *     description="订单Id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="query",
     *     description="访问唯一凭证",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="登出成功", @SWG\Schema(ref="#/definitions/Order"))
     * )
     */
    public function confirmJiedan(Request $request)
    {
        $orderId = $request->input('orderId');
        if (!$orderId) {
            $this->fail(100, '缺少参数订单ID');
        }
        // $orderInfo = $this->model->selectOne(['nurse_id' => $this->user_id, 'id' => $orderId]);
		$orderInfo = $this->model->where(['nurse_id' => $this->user_id, 'id' => $orderId])->first();

        if (empty($orderInfo)) {
            return $this->apiReturn(['code' => 100, 'msg' => '订单不存在']);
        }
        if ($orderInfo['status'] < 5 ) {
            return $this->apiReturn(['code' => 103, 'msg' => '订单操作有误']);
        }
        if ($orderInfo['status'] == 21){
            return $this->apiReturn(['code' => 103, 'msg' => '订单已申请退款']);
        }
        if ($orderInfo['status'] == 7) {
            return $this->apiReturn(['code' => 100, 'msg' => '订单已确认接单']);
        }

        if ($this->isPost()) {

            DB::beginTransaction(); //开启事务
            $data = [];
            $data['status'] = 7; //待出发
            //护士是班内还是班外,默认班内
            $data['is_bannei'] = $request->input('is_bannei',0);
			/*add by wj 2020-02-25 save保存*/
			$orderInfo->status = 7;
			$orderInfo->is_bannei = $request->input('is_bannei',0);
			$rs = $orderInfo->save();
            // $rs = $this->model->where('id', $orderId)->update($data);
            if (!$rs) {
                DB::rollback();
                return $this->fail(100, '更新失败，请重试');
            }
            DB::commit();

            //添加出诊记录
            /*modify by wj 2019-03-05 添加user_id*/
            $visitModel = new OrderVisit();
            $visit = $visitModel->selectOne(['order_id'=>$orderId]);
            if ($visit){ //如果存在,则更新记录
                $updateData = [];
                $updateData['nurse_id'] = $orderInfo['nurse_id'];
                $updateData['user_id'] = $this->sessionUser['userId'];
                $updateData['hospital_id']=$orderInfo['hospital_id'];
                $updateData['jiedan_time'] = time();
                $visitModel->where(['order_id'=>$orderId])->update($updateData);
            }else{
                $visitModel = new OrderVisit();
                $visitModel->order_id = $orderId;
                $visitModel->nurse_id = $orderInfo['nurse_id'];
                $visitModel->user_id = $this->sessionUser['userId'];
                $visitModel->hospital_id = $orderInfo['hospital_id'];
                $visitModel->jiedan_time = time();
                $visitModel->created_at = time();
                $visitModel->save();
            }

            //如果修改过时间，则取修改过的时间
            if ($orderInfo['change_book_time'] > 0) {
                $orderInfo['book_time'] = $orderInfo['change_book_time'];
            }
            //添加用户消息
            $messageService = new MessageService();
            $replaceData = [];
            $replaceData['book_time_text'] = $orderInfo['book_time'];
            $productModel = new Product();
            $product = $productModel->getInfo($orderInfo['product_id']);
            $replaceData['product_name'] = $product['product_name'];
            $messageService->add(1, $orderInfo['member_id'], 1, $replaceData, $orderInfo['id'], $orderInfo['hospital_id']);

            //发送短信通知
            $smsService = new SmsService();
            $smsService->sendNotice($orderInfo['patient_phone'], 1, 2, '已接单', $product['product_name'],'',$orderInfo['hospital_id']);

            $return = [];
            $orderService = new OrderService();
            $return['button_text'] = $orderService->getOrderButtonText(7);
            $return['id'] = $orderId;

            //给用户发送微信消息
            $member_id = $orderInfo['member_id'];
            $url = env('APP_USER_URL');
            $rs = curlGet($url.'/user/getUserInfoByBMemberId?member_id='.$member_id);
            $rs = json_decode($rs,true);
            if($rs['code'] == 200){
                $user = $rs['data'];
                $touser = $user['open_id'];
                $template_title = '派单成功提醒';
                $url = env('APP_URL').'/hlyluser2/#/orderDetail/'.$orderInfo['id'];
                $data = [
                    'first' => [
                        "value"=>urlencode("您好，您".date("Y年m月d日",strtotime($orderInfo['created_at']))."的订单"),
                        "color"=>"#173177",
                    ],
                    'keyword1'=>[
                        "value"=>urlencode($orderInfo['order_no']),
                        "color"=>"#173177",
                    ],
                    'keyword2'=>[
                        "value"=>urlencode(date("Y年m月d日 H:i",time())),
                        "color"=>"#173177",
                    ],
                    'remark'=> [
                        "value"=>urlencode("已被接单，请及时查看。"),
                        "color"=>"#173177",
                    ],
                ];
                $wxService = new WxService();
                $wxService->sendTemplate($touser, $template_title, $data, $url);
            }

            /*add by wj 2020-10-21 江门推送*/
            if(in_array($orderInfo['hospital_id'],[env('JM_HOSPITALID')])) {
                Jiangmen::instance()->changeNurse($orderId);
            }
            return $this->success($return, '提交成功');
        }
    }

    /**
     * @SWG\Post(path="/order/confirmStart",
     *   tags={"order/confirmStart"},
     *   summary="护士确认出门",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
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
     *   @SWG\Response(response=200, description="登出成功", @SWG\Schema(ref="#/definitions/Order"))
     * )
     */
    public function confirmStart(Request $request)
    {

        $orderId = $request->input('orderId');
        if (!$orderId) {
            $this->fail(100, '缺少参数订单ID');
        }
        $orderInfo = $this->model->selectOne(['nurse_id' => $this->user_id, 'id' => $orderId]);
        if (empty($orderInfo)) {
            return $this->apiReturn(['code' => 100, 'msg' => '订单不存在']);
        }
        if ($orderInfo['status'] < 7) {
            return $this->apiReturn(['code' => 103, 'msg' => '订单操作有误']);
        }
        if ($orderInfo['status'] == 21){
            return $this->apiReturn(['code' => 103, 'msg' => '订单已申请退款']);
        }
        if ($orderInfo['status'] == 9) {
            return $this->apiReturn(['code' => 102, 'msg' => '订单已出发']);
        }
        if ($this->isPost()) {
            DB::beginTransaction(); //开启事务
            $data = [];
            $data['status'] = 9; //已出发
            $rs = $this->model->where('id', $orderId)->update($data);
            if (!$rs) {
                DB::rollback();
                return $this->fail(100, '更新失败，请重试');
            }
            DB::commit();

            //更新出诊记录
            $visitModel = new OrderVisit();
            $visitModel->where(['order_id' => $orderId])->update(['start_time' => time()]);

            //如果修改过时间，则取修改过的时间
            if ($orderInfo['change_book_time'] > 0) {
                $orderInfo['book_time'] = $orderInfo['change_book_time'];
            }
            //添加用户消息
            $messageService = new MessageService();
            $replaceData = [];
            $replaceData['book_time_text'] = $orderInfo['book_time'];
            $productModel = new Product();
            $product = $productModel->getInfo($orderInfo['product_id']);
            $replaceData['product_name'] = $product['product_name'];
            //添加系统消息
            $messageService->add(3, '', 3, $replaceData, $orderInfo['id'], $orderInfo['hospital_id']);

            //发送短信通知
            $smsService = new SmsService();
            $smsService->sendNotice($orderInfo['patient_phone'], 1, 2, '已出发', $product['product_name'],'',$orderInfo['hospital_id']);


            //给用户发送微信消息
            $member_id = $orderInfo['member_id'];
            $url = env('APP_USER_URL');
            $rs = curlGet($url.'/user/getUserInfoByBMemberId?member_id='.$member_id);
            $rs = json_decode($rs,true);
            if($rs['code'] == 200){
                $visit = $visitModel->selectOne(['order_id'=>$orderId]);
                $user = $rs['data'];
                $touser = $user['open_id'];
                $template_title = '派单成功提醒';
                $url = env('APP_URL').'/hlyluser2/#/orderDetail/'.$orderInfo['id'];
                $data = [
                    'first' => [
                        "value"=>urlencode("您好，您".date("Y年m月d日",strtotime($orderInfo['created_at']))."的订单"),
                        "color"=>"#173177",
                    ],
                    'keyword1'=>[
                        "value"=>urlencode($orderInfo['order_no']),
                        "color"=>"#173177",
                    ],
                    'keyword2'=>[
                        "value"=>urlencode(date("Y年m月d日 H:i",$visit['jiedan_time'])),
                        "color"=>"#173177",
                    ],
                    'remark'=> [
                        "value"=>urlencode("护士已出发，请及时查看。"),
                        "color"=>"#173177",
                    ],
                ];
                $wxService = new WxService();
                $wxService->sendTemplate($touser, $template_title, $data, $url);
            }


            $return = [];
            $orderService = new OrderService();
            $return['button_text'] = $orderService->getOrderButtonText(9);
            $return['id'] = $orderId;


            //新增，加入地图删除队列
            //2019-02-26暂时注释
            $time = env('APP_ENV') == 'local' ? 600 : 14400;
            $job = (new MapJob($orderId))->delay($time);
            $this->dispatch($job);
            return $this->success($return, '提交成功');
        }
    }

    /**
     * @SWG\Post(path="/order/confirmArrive",
     *   tags={"order/confirmArrive"},
     *   summary="护士确认到达",
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
     *   @SWG\Response(response=200, description="登出成功", @SWG\Schema(ref="#/definitions/Order"))
     * )
     */
    public function confirmArrive(Request $request)
    {
        $orderId = $request->input('orderId');
        if (!$orderId) {
            $this->fail(100, '缺少参数订单ID');
        }
        $orderInfo = $this->model->selectOne(['nurse_id' => $this->user_id, 'id' => $orderId]);
        if (empty($orderInfo)) {
            return $this->apiReturn(['code' => 100, 'msg' => '订单不存在']);
        }
        if ($orderInfo['status'] < 9) {
            return $this->apiReturn(['code' => 103, 'msg' => '订单操作有误']);
        }
        if ($orderInfo['status'] == 11) {
            return $this->apiReturn(['code' => 102, 'msg' => '订单待确定完成']);
        }
        if ($this->isPost()) {
            DB::beginTransaction(); //开启事务
//            $data = [];
//            $data['status'] = 11; //待确定完成
//            $rs = $this->model->where('id', $orderId)->update($data);
            $ordersinfo = $this->model->getInfo($orderId);
            $ordersinfo->status = 11;
            $rs = $ordersinfo->save();
            if (!$rs) {
                DB::rollback();
                return $this->fail(100, '更新失败，请重试');
            }
            DB::commit();

            //更新出诊记录
            $visitModel = new OrderVisit();
            $visitModel->where(['order_id' => $orderId])->update(['arrive_time' => time()]);

            //给用户发送微信消息
            $member_id = $orderInfo['member_id'];
            $url = env('APP_USER_URL');
            $rs = curlGet($url.'/user/getUserInfoByBMemberId?member_id='.$member_id);
            $rs = json_decode($rs,true);
            if($rs['code'] == 200){
                $visit = $visitModel->selectOne(['order_id'=>$orderId]);
                $user = $rs['data'];
                $touser = $user['open_id'];
                $template_title = '派单成功提醒';
                $url = env('APP_URL').'/hlyluser2/#/orderDetail/'.$orderInfo['id'];
                $data = [
                    'first' => [
                        "value"=>urlencode("您好，您".date("Y年m月d日",strtotime($orderInfo['created_at']))."的订单"),
                        "color"=>"#173177",
                    ],
                    'keyword1'=>[
                        "value"=>urlencode($orderInfo['order_no']),
                        "color"=>"#173177",
                    ],
                    'keyword2'=>[
                        "value"=>urlencode(date("Y年m月d日 H:i",$visit['jiedan_time'])),
                        "color"=>"#173177",
                    ],
                    'remark'=> [
                        "value"=>urlencode("护士已到达，请及时查看。"),
                        "color"=>"#173177",
                    ],
                ];
                $wxService = new WxService();
                $wxService->sendTemplate($touser, $template_title, $data, $url);
            }

            $return = [];
            $orderService = new OrderService();
            $return['button_text'] = $orderService->getOrderButtonText(11);
            $return['id'] = $orderId;

            return $this->success($return, '提交成功');
        }
    }

    /**
     * @SWG\Post(path="/order/confirmFinish",
     *   tags={"order/confirmFinish"},
     *   summary="用户确认完成",
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
     *   @SWG\Response(response=200, description="登出成功", @SWG\Schema(ref="#/definitions/Order"))
     * )
     */
    public function confirmFinish(Request $request)
    {
        $orderId = $request->input('orderId');
        if (!$orderId) {
            $this->fail(100, '缺少参数订单ID');
        }
        $orderInfo = $this->model->selectOne(['member_id' => $this->user_id, 'id' => $orderId]);
        if (empty($orderInfo)) {
            return $this->apiReturn(['code' => 100, 'msg' => '订单不存在']);
        }
        if ($orderInfo['status'] < 11) {
            return $this->apiReturn(['code' => 103, 'msg' => '请在护士结束护理项目后再点击确认，谢谢！']);
        }
        if ($orderInfo['status'] == 13) {
            return $this->apiReturn(['code' => 102, 'msg' => '订单已完成']);
        }
        if ($this->isPost()) {
            DB::beginTransaction(); //开启事务
            $data = [];
            $data['status'] = 13; //待评价
            $rs = $this->model->where('id', $orderId)->update($data);
            if (!$rs) {
                DB::rollback();
                return $this->fail(100, '更新失败，请重试');
            }
            DB::commit();

            //更新出诊记录
            $visitModel = new OrderVisit();
            $visitModel->where(['order_id' => $orderId])->update(['finish_time' => time()]);

            //给护士增加余额 暂时以产品费用记录
            $accountService = new AccountService();
            $accountService->add($orderInfo['id'], $orderInfo['nurse_id'], 2, $orderInfo['order_fee'], $orderInfo['product_id']);

            //如果修改过时间，则取修改过的时间
            if ($orderInfo['change_book_time'] > 0) {
                $orderInfo['book_time'] = $orderInfo['change_book_time'];
            }
            //添加用户消息
            $messageService = new MessageService();
            $replaceData = [];
            $replaceData['book_time_text'] = $orderInfo['book_time'];
            $productModel = new Product();
            $product = $productModel->getInfo($orderInfo['product_id']);
            $replaceData['product_name'] = $product['product_name'];
            $messageService->add(1, $orderInfo['member_id'], 4, $replaceData, $orderInfo['id'], $orderInfo['hospital_id']);
            //添加护士消息
            $messageService->add(2, $orderInfo['nurse_id'], 6, $replaceData, $orderInfo['id'], $orderInfo['hospital_id']);
            //添加系统消息
            $messageService->add(3, '', 5, $replaceData, $orderInfo['id'], $orderInfo['hospital_id']);

            return $this->success('', '提交成功');
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
        $orderId = $request->input('orderId');
        if (!$orderId) {
            $this->fail(100, '缺少参数订单ID');
        }
        $orderInfo = $this->model->selectOne(['member_id' => $this->user_id, 'id' => $orderId]);
        if (empty($orderInfo)) {
            return $this->apiReturn(['code' => 100, 'msg' => '订单不存在']);
        }
        if ($orderInfo['delete_flag'] == 1) {
            return $this->apiReturn(['code' => 102, 'msg' => '订单已删除']);
        }
        if ($this->isPost()) {
            DB::beginTransaction(); //开启事务
            $data = [];
            $data['delete_flag'] = 1; //已删除
            $data['status'] = 17; //状态改为已取消
            $rs = $this->model->where('id', $orderId)->update($data);
            if (!$rs) {
                DB::rollback();
                return $this->fail(100, '删除失败，请重试');
            }
            DB::commit();

            return $this->success('', '删除成功');
        }
    }

    /**
     * @SWG\Post(path="/order/beforeSave",
     *   tags={"order/beforeSave"},
     *   summary="添加订单前获取相关数据接口",
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
     *     name="token",
     *     in="query",
     *     description="访问唯一标记",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="增加成功", @SWG\Schema(ref="#/definitions/Order"))
     * )
     */
    public function beforeSave(Request $request)
    {
        $rules = [
            'product_id' => 'required|numeric',
            'activity_type' => 'required',
        ];
        $attributes = [
            'product_id' => '产品Id',
            'activity_type' => '活动类型',
            'activity_id' => '活动Id',
            'sub_product_id' => '搭配产品Id',
        ];

        $haocai_need = $request->input('haocai_need',0);

        $validator = Validator::make($request->all(), $rules, [], $attributes);
        $validator->sometimes(['activity_id'], 'required|numeric', function ($input) {
            return $input->activity_type != 99999;  //使用优惠活动时必填
        });
        $validator->sometimes(['sub_product_id'], 'required|string', function ($input) {
            return $input->activity_type == 1;   //搭配套餐时必填项
        });

        $messages = $validator->messages();
        if ($validator->failed()) {
            foreach ($messages->all() as $message) {
                return $this->fail(100, $message);
            }
        }
        $productInfo[] = $mainProduct = $this->productRep->getById($request->input('product_id'));
        $product_fee = $productInfo[0]['product_fee'];

        if ($request->input('patient_id')) {
            $patient = $this->memberPatientRep->getById($request->input('patient_id'));
        } else {
            $patient = $this->memberPatientRep->getDefaultPatient($this->user_id,$mainProduct->hospital_id) ?: '';
        }
        //add by Hex @20191213 for获取就诊人最新的服务地址
        if ($request->input('id_card')??'') {
            $patient = $this->memberPatientRep->getLastPatient($this->user_id,$request->input('id_card'),$mainProduct->hospital_id)?:'';
        }
        //2019.01.23业务新增，判断产品是否院内，如果是院内，修改用户地址
        if(!empty($patient)){
            if($mainProduct->type_id == 2){
                $patient['province'] = $mainProduct->address;
                $patient['street'] = $mainProduct->address_detail;
                $patient['address'] = '';
                $patient['address_extra'] = '';
            }
        }

        //begin判断白名单
        $is_white = false;
        if($patient){
            $Whitelist = new Whitelist();
            //判断是否在白名单内
            $WhitelistRepository = new WhitelistRepository($Whitelist);
            $white_info = $WhitelistRepository->checkWhite($patient);
            if($white_info){
                $is_white = true;
            }
        }

        //end判断白名单
        //begin计算价格
        $result = $this->getServerPrice($request , $is_white , $product_fee , $mainProduct , $patient , $productInfo,$mainProduct->type_id);
        //end计算价格

        //判断产品是否有耗材
        //if($haocai_need){
            //begin计算耗材
            $result = $this->getHaoCai($result , $request , $haocai_need);
            //end计算耗材
        //}

        //begin服务保障是否记住
        $result = $this->remeberPro($result , $request , $mainProduct);
        //end服务保障是否记

        //如果付款金额小于0，重置付款金额和优惠金额
        if($result['pay_fee'] <= 0){
            $result['pay_fee'] = 0.01;
            $discount_fee = 0 ;
            foreach ($productInfo as $product){
                $discount_fee += $product['taocan_fee'];
            }
            $result['discount_fee'] = bcadd($discount_fee , $result['shangmenFee'],2);
        }

        //查询上门费描述
        $desc = ShangmenFeeDesc::getDetailByHospitalTag($request->input('tag'));

        $result['is_white'] = $is_white;

        $result['shangmenfee_desc'] = $desc;

        return $this->success($result);
    }

    /**
     * @todo 计算商品的初始价格
     */
    private function getServerPrice($request , $is_white , $product_fee , $mainProduct , $patient , $productInfo,$pro_type_id){

        $discount_fee = 0;
        $pay_fee = 0;
        $num = 1;
        $server_num = 1;

        if (in_array($request->input('activity_type'), [1, 2, 3, 4])) {
            $activity = $this->taocanRep->getById($request->input('activity_id'));
            if ($request->input('activity_type') == 1) {

                $sub_product_id = $request->input('sub_product_id');
                $sub_product_id = str_replace('，', ',', $sub_product_id);
                $sub_product_id_arr = explode(',', $sub_product_id);
                $productInfo[0]['taocan_fee'] = $activity->taocan_fee;
                $subFee = $this->taocanRep->getTaocanSubFee($activity, $sub_product_id_arr);
                $pay_fee = $activity->taocan_fee + $subFee;
                //获取选择的子产品数据
                $taocanSub = $this->taocanRep->getTaocanSubs($activity, $sub_product_id_arr);
                foreach ($taocanSub as $k => $v) {
                    $product = $v->product;
                    $product['taocan_fee'] = $v->sub_taocan_fee;
                    $product_fee += $v->product->product_fee;
                    $productInfo[] = $product;
                }

                $server_num = 1 + count($sub_product_id_arr);

                if(!$is_white){
                    $discount_fee = $product_fee - $pay_fee;
                }

            } elseif ($request->input('activity_type') == 2) {

                $productInfo[0]['taocan_fee'] = round($activity->service_fee/$activity->service_times,2);
                $pay_fee = $activity->service_fee;
                $num = $activity->service_times;
                $server_num = $num;

                if(!$is_white){
                    $discount_fee = $product_fee * $activity->service_times - $activity->service_fee;
                }

            } elseif ($request->input('activity_type') == 3 && !$request->input('no_youhui',0)) {

                $productInfo[0]['taocan_fee'] = $product_fee;

                if(!$is_white){

                    $pay_fee =  $product_fee - $activity->first_youhui;
                    $discount_fee = $activity->first_youhui;
                    $taocan = $this->taocanRep->getActivityList($request->input('product_id'),3);
                    $result['first'] = [
                        'first_id' => $taocan['id'],
                        'first_youhui' => $taocan['first_youhui']
                    ];
                }

            } elseif ($request->input('activity_type') == 4 && !$request->input('no_youhui',0)) {

                $productInfo[0]['taocan_fee'] = $product_fee;

                if(!$is_white){

                    $pay_fee =  $product_fee * ($activity->discount_lidu / 100);
                    $discount_fee = $product_fee - $pay_fee;

                    $discount = $this->taocanRep->getActivityList($request->input('product_id'),4);
                    $result['discount'] = [
                        'discount_id' => $discount['id'],
                        'discount_youhui' => $discount_fee
                    ];
                }
            }
        } elseif ($request->input('activity_type') == 5 && !$request->input('no_youhui',0)) {

            $productInfo[0]['taocan_fee'] = $product_fee;

            if(!$is_white){

                $activity = $this->ticketRep->getById($request->input('activity_id'));
                $pay_fee = $product_fee - $activity->ticket_money;
                $discount_fee = $activity->ticket_money;

                $result['ticket'] = [
                    'ticket_id' => $activity->id,
                    'ticket_youhui' => $discount_fee
                ];
            }

        }elseif($request->input('activity_type') == 99999) {
            $productInfo[0]['taocan_fee'] = $product_fee;
            $pay_fee = $product_fee;
        }

        //没有活动时，判断是否有首单优惠
        if($request->input('activity_type') == 99999 && !$request->input('no_youhui',0)){
            $flag = $this->orderRep->getFirst($this->user_id);
            $taocan = $this->taocanRep->getActivityList($request->input('product_id'),3);
            $discount = $this->taocanRep->getActivityList($request->input('product_id'),4);
            if($discount){
                $discount['youhui'] = round($mainProduct['product_fee'] - $mainProduct['product_fee']*$discount['discount_lidu']/100,2);
            }
            $ticket = $this->ticketRep->getUserTicketMax($this->user_id,$mainProduct->hospital_id);

            $taocan_fee = 0;
            $discount_fee2 = 0;
            $ticket_fee = 0;

            if($flag && $taocan){
                $taocan_fee = $taocan['first_youhui'];
            }
            if($discount){
                $discount_fee2 = $discount['youhui'];
            }
            if($ticket->isNotEmpty()){
                $ticket_fee = $ticket[0]['ticket_money'];
            }
            $max = get_max($taocan_fee,$discount_fee2,$ticket_fee);
            if($max == $taocan_fee && $taocan_fee > 0){
                $result['first'] = [
                    'first_id' => $taocan['id'],
                    'first_youhui' => $taocan['first_youhui']
                ];
                $pay_fee = round($pay_fee,2) - $taocan['first_youhui'];
                $discount_fee = $discount_fee + $taocan['first_youhui'];
            }else if($max == $discount_fee2 && $discount_fee2 > 0){
                $result['discount'] = [
                    'discount_id' => $discount['id'],
                    'discount_youhui' => $discount['youhui']
                ];
                $pay_fee = round($pay_fee,2) - $discount['youhui'];
                $discount_fee = $discount_fee + $discount['youhui'];
            }else if($max == $ticket_fee && $ticket_fee > 0 && $ticket->isNotEmpty()){
                $result['ticket'] = [
                    'ticket_id' => $ticket[0]['id'],
                    'ticket_youhui' => $ticket[0]['ticket_money']
                ];
                $pay_fee = round($pay_fee,2) - $ticket[0]['ticket_money'];
                $discount_fee = $discount_fee + $ticket[0]['ticket_money'];
            }

        }

        if($is_white){
            $pay_fee = 0;
        }

        //是否有耗材
        foreach ($productInfo as &$vo){
            $count = ProductHaoCai::where(['is_del'=>1,'product_id'=>$vo['id']])->count();
            if ($count > 0){
                $vo['hashaocai'] = 1;
            }else{
                $vo['hashaocai'] = 0;
            }
        }

        $result['products'] = $productInfo;

        //免费服务
        $service_model = new GiveService();
        $result['services'] = $service_model->where(['is_del'=>1,'product_id'=>$request->input('product_id')])->get();

        //增值服务
        $AddedService = new AddedService();
        $result['added_service'] = $AddedService->where(['is_del'=>1 ,'is_show'=>1, 'product_id'=>$request->input('product_id')])->get();


        $hospitalInfo = $this->getHospitalInfo($mainProduct->hospital_id);
        $hospitalInfo = $hospitalInfo["data"];

        $result['patient'] = $patient;
        $result['shangmenFee'] = $patient ? $this->shangmenRep->getShangmenFee($hospitalInfo, $patient) : 0;
        $result['shangmenFeeText'] = $patient ? $this->shangmenRep->getShangmenText($hospitalInfo, $patient) : "";
        /* add by wj 2020-08-27 交通费*/
        if($request->input('activity_type') == 1 && !empty($hospitalInfo['info']) && $hospitalInfo['info']['is_jtf'] == 2)
            $result['shangmenFee']  = $result['shangmenFee'];
        else
            $result['shangmenFee']  = $server_num * $result['shangmenFee'];

        if($pro_type_id == 2){
            $result['shangmenFee'] = 0;
            $result['shangmenFeeText'] = '';
        }

        $result['order_fee'] = $product_fee;
        $result['pay_fee'] = round( (round($pay_fee,2) + $result['shangmenFee']) , 2);
        $result['discount_fee'] = round($discount_fee,2);
        $result['num'] = $num;
        $service_week = $mainProduct->service_week?getWeekStr($mainProduct->service_week):'工作日';
        $service_time = $mainProduct->service_time?:'00:00 - 00:00';
        $result['service_time'] = $service_week.' '.$service_time;

        /* add by wj 2019-11-13 划分时间段 */
        $stime = explode('-', $service_time);
        $hours = explode(':', trim($stime[0]));
        $miniutes = explode(':', trim($stime[1]));
        $result['min_hour'] = $hours[0];
        $result['max_hour'] = $miniutes[0];
        $result['min_mini'] = $hours[1];
        $result['max_mini'] = $miniutes[1];

        return $result;
    }

    /*
     * @todo 计算耗材
     */
    private function getHaoCai($result , $request , $haocai_need){

        $product_id_arr = [$request->input('product_id')];

        if($request->input('activity_type') == 1){
            $sub_product_id = $request->input('sub_product_id');
            $sub_product_id = str_replace('，', ',', $sub_product_id);
            $sub_product_id_arr = explode(',', $sub_product_id);
            $product_id_arr = array_merge($sub_product_id_arr,$product_id_arr);
        }

        $haocai_data = ProductHaoCai::where(array(
//            'product_id'=>$request->input('product_id'),
            'is_del'=>1
        ))->whereIn('product_id',$product_id_arr)->get();

        if($haocai_data->isEmpty()){
            $haocai_need = 0;
            $result['has_haocai'] = 0;
        }else{
            $result['has_haocai'] = 1;
        }

        //判断耗
        $haocai_info_input = $request->input('haocai_info');
        $haocai_price = 0.00;

        $data = array();
        $data_info = array();
        $haocai_info = array();

        if(!empty(json_decode($haocai_info_input,true))){
            $pro_id = array();
            $haocai_info_input = json_decode($haocai_info_input,true);
            //判断是不是套餐类的

            foreach ($haocai_info_input as $key => $val){
                $pro_id[] = $val['product_id'];
            }

            $pro_diff = array_diff($product_id_arr,$pro_id);

            foreach ($product_id_arr as $index => $item){

                $new_data = array();

                if(in_array($item,$pro_id)){

                    foreach ($haocai_info_input as $key => $val){

                        if($val['product_id'] == $item){
                            $price = 0.00;
                            foreach ($val['haocai_info'] as $k => $v){
                                $haocai = ProductHaoCai::where(array('product_id'=>$val['product_id'],'id'=>$v['id'],'is_del'=>1))->first()->toArray();
                                $haocai_price += $haocai['price'] * $v['buyNum'];
                                $data['id'] = $v['id'];
                                $data['buyNum'] = $v['buyNum'];
                                $data['price'] = $v['price'];
                                $data['name'] = $v['name'];
                                $price += $v['price'] * $v['buyNum'];
                                $data_info[] = $data;
                            }
                        }
                    }

                    if($data_info){

                        $new_data['product_id'] = $item;
                        $new_data['price'] = round($price , 2);
                        $new_data['haocai_info'] = $data_info;
                        $data_info = array();
                        $haocai_info[] = $new_data;
                    }

                }elseif (in_array($item,$pro_diff)){
                    
                    //说明是用户没有选择的耗材，默认数量全部为1
                    //不知道为什么添加，导致套餐出错，暂时注释，gyy操作，2019-09-03
                    /*
                    //$haocai_price += ProductHaoCai::where(array('is_default'=>1,'is_del'=>1))->whereIn('product_id',$pro_diff)->sum('price');
                    foreach ($pro_diff as $key => $val){
                        if($val == $item){
                            $haocai = ProductHaoCai::where(array('product_id'=>$val,'is_del'=>1))->get();
                            if($haocai->isNotEmpty()){
                                $price = 0.00;
                                foreach ($haocai as $k => $v){
                                    $haocai_price += $v['price'] * 1;
                                    $data['id'] = $v['id'];
                                    $data['buyNum'] = 1;
                                    $data['price'] = $v['price'];
                                    $data['name'] = $v['name'];
                                    if($v->is_default == 1){
                                        $price += $v['price'];
                                    }
                                    $data_info[] = $data;
                                }
                            }else{
                                $price = 0;
                            }
                        }
                    }

                    if($data_info){
                        $new_data['price'] = round($price , 2);
                        $new_data['haocai_info'] = $data_info;
                        $new_data['product_id'] = $item;

                        $haocai_info[] = $new_data;
                        $data_info = array();
                    }*/
                }
            }

            $result['haocai'] = array_merge($haocai_info);

        }else{
            //获取全部耗材并且设置数量为1
            //修改,获取全部默认选中的耗材，并且设置数量为1
            $where = array(
                'is_default'=>1,
                'is_del'=>1
            );
            $buyNum = 1;
            if($request->input('activity_type') == 2){
                $buyNum = $result['num'];
            }
            $haocai_price = ProductHaoCai::where($where)->whereIn('product_id',$product_id_arr)->sum('price');

            $haocai_price *= $buyNum;

            foreach ($product_id_arr as $key => $val){
                $haocai = ProductHaoCai::where(array('product_id'=>$val,'is_del'=>1,'is_default'=>1))->get();
                if($haocai->isNotEmpty()){
                    $price = 0.00;
                    foreach ($haocai as $k => $v){
                        $data['id'] = $v['id'];
                        $data['buyNum'] = $buyNum;
                        $data['price'] = $v['price'];
                        $data['name'] = $v['name'];
                        //if($v->is_default == 1){
                            $price += $v['price'] * $buyNum;
                        //}
                        $data_info[] = $data;
                    }
                    $haocai_info[$key]['product_id'] = $val;
                    $haocai_info[$key]['price'] = $price;
                    $haocai_info[$key]['haocai_info'] = $data_info;
                    $data_info = array();
                }
            }

            $result['haocai'] = $haocai_info;
        }

        $haocai_price = round($haocai_price , 2);

        if($haocai_need == 1) {
            $result['haocai_fee'] = $haocai_price ;
            $result['order_fee'] = round( $result['order_fee'] + $haocai_price , 2);
            $result['pay_fee'] = round($result['pay_fee'] + $haocai_price , 2);
        }

        return $result;
    }

    /**
     * @todo 是否记住保障
     */
    private function remeberPro($result , $request , $mainProduct){

        //服务保障是否记住
        $productRememberModel = new ProductRemember();
        $total = $productRememberModel->where(['product_id'=>$request->input('product_id'),'member_id'=>$this->user_id])->count();
        if ($total > 0){
            $result['is_remember'] = 1;
        }else{
            $result['is_remember'] = 0;
        }

        if ($mainProduct['product_guarantee']){
            $result['product_guarantee'] = explode('|', $mainProduct['product_guarantee']);
        }else{
            $result['product_guarantee'] = '';
        }

        return $result;
    }

    /**
     * @SWG\Post(path="/order/add",
     *   tags={"order/add"},
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
            'patient_id' => 'required|numeric',
            'give_service' => 'required|numeric',
            'book_time' => 'required',
            //'medical_desc' => 'required',
            'shangmen_fee' => 'required',
            'activity_type' => 'required',
            'product_nums' => 'required',
            'haocai_need' => 'required',
        ];
        $attributes = [
            'product_id' => '产品Id',
            'patient_id' => '患者Id',
            'give_service' => '赠送服务',
            'book_time' => '服务时间',
            'medical_desc' => '病情描述',
            'shangmen_fee' => '交通费',
            'activity_type' => '活动类型',
            'activity_id' => '活动Id',
            'sub_product_id' => '搭配产品Id',
            'product_nums' => '产品数量',
            'haocai_need' => '是否需要耗材'
        ];

        //患者检查
        $patient_id = $request->input('patient_id');
        if (!$patient_id){
            return $this->fail(100,'请先创建患者信息');
        }

        $medical_desc = $request->input('medical_desc');
        $medical_special_desc =  $request->input('medical_special_desc');
        $select_desc_imgs = $request->input('select_desc_imgs');
        $video_list =  $request->input('video_list');

        if(! $medical_desc  && ! $medical_special_desc && !$select_desc_imgs && !$video_list){
            return $this->fail(100,'请填写病情描述');
        }

        /* add by wj 2020-08-24 健康码*/
        $hospitalInfo = $this->getHospitalInfo($this->hospital_id);
        $hospitalInfo = $hospitalInfo["data"];
        if(!empty($hospitalInfo['info']) && $hospitalInfo['info']['is_jkm'] == 1){
            $jkm = $request->input('jkm');
            if(empty($jkm))
                return $this->fail(100,'请上传健康码');
        }

        $validator = Validator::make($request->all(), $rules, [], $attributes);
        $validator->sometimes(['activity_id'], 'required|numeric', function ($input) {
            return $input->activity_type != 99999;  //使用优惠活动时必填
        });
        $validator->sometimes(['sub_product_id'], 'required|string', function ($input) {
            return $input->activity_type == 1;   //搭配套餐时必填项
        });
        $messages = $validator->messages();
        if ($validator->failed()) {
            foreach ($messages->all() as $message) {
                return $this->fail(100, $message);
            }
        }

        $patient = $this->memberPatientRep->getById($request->input('patient_id'));

        /*modify by gyy 2019-04-11 判断该患者是否存在黑名单*/
        $Blacklist = new Blacklist();
        $blackInfo = $Blacklist->where(['hospital_id'=>$this->hospital_id , 'id_card' => $patient['patient_id_card'] , 'is_del' => 1])->first();

        if($blackInfo){
            return $this->fail(100, '本时间段已约满，请见谅');
        }

        //判断是否在白名单内
        $Whitelist = new Whitelist();

        $patient_id_card = $patient['patient_id_card'];
        $hospital_id = $patient['hospital_id'];

        $white_info = $Whitelist->where("id_card" , $patient_id_card)
                                ->where("hospital_id" , $hospital_id)
                                ->where("status",1)
                                ->where('is_del',1)
                                ->first();

        $is_white = false;
        if($white_info){
            $is_white = true;
        }

        if ($patient->member_id != $this->user_id) {
            return $this->fail(103, '患者有误');
        }
        if (!isMobile($request->input('patient_phone'))) {
            return $this->fail(100, '请编辑患者完善联系电话');
        }

        $book_time_timestamp = strtotime(str_replace('T', ' ', $request->input('book_time')));
        $week = date('N', $book_time_timestamp);
        $hour = date('G.i', $book_time_timestamp);
        // $start = strtotime('2018-02-15 00:00:00');
        // $end = strtotime('2018-02-25 23:59:59');
        // if ($book_time_timestamp >= $start && $book_time_timestamp <= $end) {
        //     return $this->fail(100, '2月15日至2月25日暂不提供服务,请原谅');
        // }
        /*add by wj 2019-01-18 增加假期判断*/
        $rs = HolidayConfig::where(['hospital_id'=>$this->hospital_id,'is_del'=>1])
                ->where('begintime', '<=', $book_time_timestamp)
                ->where('endtime', '>=', $book_time_timestamp)
                ->first();
        if($rs){
            /*modify by wj 2019-02-21 修改提示语*/
            return $this->fail(100,'当前时间不提供服务，请见谅');
        }
        //获取产品可服务时间
        $productModel = new Product();
        $product = $productModel->find( $request->input('product_id'));

        /*modify by gyy 2019-11-05  增加有些服务是强制上传病情描述图片*/
        $is_need_desc_img = $product->is_need_desc_img;
        //判断病情描述图片是否必填
        if($is_need_desc_img == 1){
            if(!$select_desc_imgs){
                return $this->fail(100,'请上传病情描述的图片');
            }
        }

        if($request->input('activity_type') == 1){

            $sub_product_id = $request->input('sub_product_id');
            $sub_product_id = str_replace('，', ',', $sub_product_id);
            $sub_product_id_arr = explode(',', $sub_product_id);
            $sub_product_id_arr[] = $request->input('product_id');

            $sub_product_info = $productModel->whereIn("id" , $sub_product_id_arr)->where("is_need_desc_img",1)->first();
            if($sub_product_info){

                if(!$select_desc_imgs){
                    return $this->fail(100,'请上传病情描述的图片');
                }
            }
        }
        
        if(!empty($patient)){
            if($product->type_id == 2){
                $patient->province = $product->address;
                $patient->street = $product->address_detail;
                $patient->address = '';
                $patient->address_extra = '';
            }
        }
        //modify by Hex @20190412 for 服务时间判断调整
        $minHour = $product->service_time?date('G.i', strtotime(explode(' - ',$product->service_time)[0])):8.30;
        $maxHour = $product->service_time?date('G.i', strtotime(explode(' - ',$product->service_time)[1])):17.30;
        $weekArr = $product->service_week?explode(',', $product->service_week):[1,2,3,4,5];
        if (!in_array($week, $weekArr) || !($hour >= $minHour && $hour <= $maxHour)) {
            return $this->fail(100, '当前时间不提供服务，请见谅');
        }
        //end
        if ($book_time_timestamp <= time()) {
            return $this->fail(100, '请选当前之后的时间');
        }
        $productInfo = $this->productRep->getById($request->input('product_id'));

        //不使用优惠
        if ($request->input('activity_type') == 99999) {

            return $this->activityType9999($request , $product , $patient , $productInfo , $is_white);

        } else { // 1搭配套餐、2预购套餐、3首单优惠、4打折、5优惠券

            //针对首单优惠，将之前未支付的首单取消
            if($request->input('activity_type') == 3){
                Order::where(array('activity_type'=>3,'member_id'=>$this->user_id))->update(array('status'=>17));
            }

            return $this->useTaocan($request , $patient , $productInfo , $is_white , $product);
        }
    }

    /**
     * @todo 不使用优惠券
     */
    private function activityType9999($request , $product , $patient , $productInfo , $is_white){

        $input = $request->all();

        $insert = $this->orderRep->formart($input , $product->hospital_id, $this->user_id, $patient, $productInfo , '' ,$is_white);

        $insert['activity_type'] = 0;

        DB::beginTransaction();
        try{

            //判断是否有病情描述
            $medical_special_desc = $request->input('medical_special_desc');
            if(!empty($medical_special_desc)){
                $insert['medical_special_desc'] = $medical_special_desc[0]['desc'];
            }

            $modelInfo = $this->orderRep->store($insert);

            /*modify by 葛灬先生 2019-02-26 增加product_type_id字段*/
            $modelInfo->product_type_id = $productInfo->type_id;
            //查询价格配置表是不是为空
            $sub_price = ProductSubPrice::where(array('product_id'=>$product->id,'is_del'=>1))->get()->toArray();

            if(!empty($sub_price)){
                $modelInfo->sub_price = \GuzzleHttp\json_encode($sub_price);
            }
            if($request->input('haocai_need') == 1){

                $haocai_info =  $request->input('haocai_info');
                $haocai_info = json_decode($haocai_info,true);
                $haocai_price = 0.00;
                foreach ($haocai_info as $key => $val){

                    foreach ($val['haocai_info'] as $k1 => $v1){
                        $haocai = ProductHaoCai::where(array('product_id'=>$val['product_id'],'id'=>$v1['id'],'is_del'=>1))->first()->toArray();
                        $haocai_price += $haocai['price'] * $v1['buyNum'];
                    }

                }
                $modelInfo->haocai_fee = $haocai_price;
                //$modelInfo->order_fee = $modelInfo->order_fee + $haocai_price;
                $modelInfo->pay_fee = $modelInfo->pay_fee + $haocai_price;
            }

            //begin增加耗材详情
            $modelInfo = $this->addHaoCai($request , $modelInfo);
            //end增加耗材详情

            //如果支付小于0，则为0.01
            if ($modelInfo->pay_fee <= 0)
                $modelInfo->pay_fee = 0.01;
            $modelInfo->save();

            //add by Hex@20200623 for 保存意向护士
            if($input['order_nurse_id'] && $input['order_nurse_name']){
                $this->saveOrderNurse($modelInfo,$input['order_nurse_id'],$input['order_nurse_name']);
            }

            DB::commit();

            return $this->success($modelInfo);
        }catch (\Exception $e){
            DB::rollback();
            return $this->fail(100, '增加失败，请重试');
        }
    }

    /**
     * @todo 使用套餐的情况
     */
    private function useTaocan($request , $patient , $productInfo , $is_white , $product){

        if (in_array($request->input('activity_type'), [1, 2, 3, 4])) {
            $activity = $this->taocanRep->getById($request->input('activity_id'));
            if ($activity) {
                if ($activity->taocan_type != $request->input('activity_type'))
                    return $this->fail(100, '活动类型不匹配，请核实数据');
                elseif ($activity->product_id != $request->input('product_id'))
                    return $this->fail(100, '产品Id不匹配，请核实数据');
                elseif(($request->input('activity_type') == 2) && $activity->service_times != $request->input('product_nums'))
                    return $this->fail(100, '产品数量不匹配，请核实数据');
                else{

                    $sub_product_id_arr = array();

                    if ($request->input('activity_type') == 1) { //获取搭配套餐总价格
                        $sub_product_id = $request->input('sub_product_id');
                        $sub_product_id = str_replace('，', ',', $sub_product_id);
                        $sub_product_id_arr = explode(',', $sub_product_id);
                        $subFee = $this->taocanRep->getTaocanSubFee($activity, $sub_product_id_arr);
                        $activity->total_taocan_fee = $activity->taocan_fee + $subFee;
                    }

                    $insert = $this->orderRep->formart($request->all(), $product->hospital_id, $this->user_id, $patient, $productInfo, $activity , $is_white);

                    DB::beginTransaction();
                    try {

                        $insert['medical_special_desc'] = "";
                        $modelInfo = $this->orderRep->store($insert);
                        if($request->input('haocai_need') == 1){
                            $haocai_info =  $request->input('haocai_info');
                            $haocai_info = json_decode($haocai_info,true);
                            $haocai_price = 0.00;
                            foreach ($haocai_info as $key => $val){

                                foreach ($val['haocai_info'] as $k1 => $v1){
                                    $haocai = ProductHaoCai::where(array('product_id'=>$val['product_id'],'id'=>$v1['id'],'is_del'=>1))->first()->toArray();
                                    $haocai_price += $haocai['price'] * $v1['buyNum'];
                                }

                            }
                            $modelInfo->haocai_fee = $haocai_price;
                            //$modelInfo->order_fee = $modelInfo->order_fee + $haocai_price;
                            $modelInfo->pay_fee = $modelInfo->pay_fee + $haocai_price;
                        }
                        /*modify by 葛灬先生 2019-02-26 增加product_type_id字段*/
                        $modelInfo->product_type_id = $productInfo->type_id;
                        //如果支付小于0，则为0.01
                        if ($modelInfo->pay_fee <= 0)
                            $modelInfo->pay_fee = 0.01;
                        $modelInfo->save();

                        DB::commit();
                    }catch (\Exception $e){
                        DB::rollback();
                        return $this->fail(100, '增加失败，请重试');
                    }


                    if (in_array($request->input('activity_type'), [1, 2])) {

                        //1-搭配套餐 2-预购套餐
                        return $this->activityType12($request , $product , $modelInfo , $activity , $patient , $is_white , $productInfo , $sub_product_id_arr);
                    } else {

                        //3-首单优惠  4-打折
                        if($is_white){ //如果是白名单的时候不使用优惠券和折扣和首单优惠
                            $modelInfo->activity_type = 0;
                            $modelInfo->activity_id = 0;
                            $modelInfo->discount_fee = 0;
                        }
                        return $this->activityType34($request , $modelInfo);
                    }
                }
            } else {
                return $this->fail(100, '暂无此活动，请核实数据');
            }

        } elseif ($request->input('activity_type') == 5) {

            //使用优惠券
            return $this->activityType5($request , $patient , $productInfo , $is_white);
        }
    }


    /**
     * @todo 1-搭配套餐 2-预购套餐
     */
    private function activityType12($request , $product , $modelInfo , $activity , $patient , $is_white , $productInfo , $sub_product_id_arr){

        DB::beginTransaction();
        try {

            $orderNoArr['hospital_id'] = $product->hospital_id;
            $orderNoArr['user_id'] = $this->user_id;
            $orderNoArr['parent_order_no'] = $modelInfo->order_no;
            $medical_special_desc = $request->input('medical_special_desc');
            if ($request->input('activity_type') == 1) {

                //添加主产品数据
                $orderNoArr['order_no'] = $modelInfo->order_no . '-1';
                $orderNoArr['current_fee'] = $activity->taocan_fee;
                $orderNoArr['product_info'] = $productInfo;
                $subCount = count($sub_product_id_arr) + 1;
                $orderNoArr['shangmen_fee'] = round($modelInfo->shangmen_fee / $subCount,2);

                $insertSub = $this->orderRep->formartSub($request->all(), $patient, $orderNoArr , "" , $is_white);
                $insertSub['medical_special_desc'] = "" ;

                $mainorder = $this->orderRep->store($insertSub);

                //判断是否有病情描述
                if(!empty($medical_special_desc)){
                    foreach ($medical_special_desc as $key => $val){
                        if($val['product_id'] == $mainorder->product_id){
                            $mainorder->medical_special_desc = $val['desc'];
                        }
                    }
                }

                if($request->input('haocai_need') == 1){
                    $haocai_info =  $request->input('haocai_info');
                    $haocai_info = json_decode($haocai_info,true);
                    $data = array();
                    $datainfo = array();
                    $haocai_price = 0.00;
                    foreach ($haocai_info as $key => $val){
                        if($val['product_id'] == $mainorder->product_id){
                            foreach ($val['haocai_info'] as $k1 => $v1){
                                $data['order_id'] = $mainorder->id;
                                $data['product_id'] = $val['product_id'];
                                $data['haocai_id'] = $v1['id'];
                                $data['haocai_num'] = $v1['buyNum'];
                                $data['created_at'] = time();
                                $haocai = ProductHaoCai::where(array('product_id'=>$val['product_id'],'id'=>$v1['id'],'is_del'=>1))->first()->toArray();
                                $data['haocai_name'] = $haocai['name'];
                                $data['haocai_price'] = $haocai['price'];
                                /*add by wj 2019-10-14 增加his字段*/
                                if(!empty($haocai['xmdm'])){
                                    $data['xmdm'] = $haocai['xmdm'];
                                    $data['sysl'] = $haocai['sysl'];
                                    $data['sapwlh'] = $haocai['sapwlh'];
                                    $data['auto_select'] = $haocai['auto_select'];
                                }
                                $datainfo[] = $data;
                                $haocai_price += $haocai['price'] * $v1['buyNum'];
                            }
                        }
                    }

                    OrderHaoCai::insert($datainfo);
                    $mainorder->haocai_fee = $haocai_price;
                    //$mainorder->order_fee = $mainorder->order_fee + $haocai_price;
                    $mainorder->pay_fee = $mainorder->pay_fee + $haocai_price;
                }
                /*modify by 葛灬先生 2019-02-26 增加product_type_id字段*/
                $mainorder->product_type_id = $productInfo->type_id;
                //如果支付小于0，则为0.01
                if ($mainorder->pay_fee <= 0)
                    $mainorder->pay_fee = 0.01;
                $mainorder->save();


                //add by Hex@20200623 for 保存意向护士
                if($request->input('order_nurse_id') && $request->input('order_nurse_name')){
                    $this->saveOrderNurse($mainorder,$request->input('order_nurse_id'),$request->input('order_nurse_name'));
                }

                //获取选择的子产品数据
                $taocanSub = $this->taocanRep->getTaocanSubs($activity, $sub_product_id_arr);

                foreach ($taocanSub as $k => $v) {

                    $orderNoArr['order_no'] = $modelInfo->order_no . '-' . ($k + 2);
                    $orderNoArr['current_fee'] = $v['sub_taocan_fee'];
                    $orderNoArr['product_info'] = $v->product;
                    $insertSub = $this->orderRep->formartSub($request->all(), $patient, $orderNoArr,"",$is_white);
                    $insertSub['medical_special_desc'] = "" ;
                    $childorder = $this->orderRep->store($insertSub);

                    if(!empty($medical_special_desc)){
                        foreach ($medical_special_desc as $key => $val){
                            if($val['product_id'] == $v['product_id']){
                                $childorder['medical_special_desc'] = $val['desc'];
                            }
                        }
                    }

                    if($request->input('haocai_need') == 1){
                        $haocai_info =  $request->input('haocai_info');
                        $haocai_info = json_decode($haocai_info,true);
                        $data = array();
                        $datainfo = array();
                        $haocai_price = 0.00;
                        foreach ($haocai_info as $key => $val){
                            if($val['product_id'] == $childorder->product_id){
                                foreach ($val['haocai_info'] as $k1 => $v1){
                                    $data['order_id'] = $childorder->id;
                                    $data['product_id'] = $val['product_id'];
                                    $data['haocai_id'] = $v1['id'];
                                    $data['haocai_num'] = $v1['buyNum'];
                                    $data['created_at'] = time();
                                    $haocai = ProductHaoCai::where(array('product_id'=>$val['product_id'],'id'=>$v1['id'],'is_del'=>1))->first()->toArray();
                                    $data['haocai_name'] = $haocai['name'];
                                    $data['haocai_price'] = $haocai['price'];
                                    /*add by wj 2019-10-14 增加his字段*/
                                    if(!empty($haocai['xmdm'])){
                                        $data['xmdm'] = $haocai['xmdm'];
                                        $data['sysl'] = $haocai['sysl'];
                                        $data['sapwlh'] = $haocai['sapwlh'];
                                        $data['auto_select'] = $haocai['auto_select'];
                                    }
                                    $datainfo[] = $data;
                                    $haocai_price += $haocai['price'] * $v1['buyNum'];
                                }
                            }
                        }

                        OrderHaoCai::insert($datainfo);
                        $childorder->haocai_fee = $haocai_price;
                        //$childorder->order_fee = $childorder->order_fee + $haocai_price;
                        $childorder->pay_fee = $childorder->pay_fee + $haocai_price;
                        //如果支付小于0，则为0.01
                    }
                    /*modify by 葛灬先生 2019-02-26 增加product_type_id字段*/
                    $childorder->product_type_id = $productInfo->type_id;
                    if ($childorder->pay_fee <= 0)
                        $childorder->pay_fee = 0.01;
                    $childorder->save();

                    //add by Hex@20200623 for 保存意向护士
                    if($request->input('order_nurse_id') && $request->input('order_nurse_name')){
                        $this->saveOrderNurse($childorder,$request->input('order_nurse_id'),$request->input('order_nurse_name'));
                    }
                }
            } elseif ($request->input('activity_type') == 2) {

                $AddedService = new AddedService();
                $select_added_servce = $request->input('select_added_servce');

                $added_service_money = 0;
                if($select_added_servce){

                    $select_added_servce = json_decode($select_added_servce , true);
                    $added_services_fee  = $AddedService->whereIn("id", $select_added_servce)->get()->sum('service_dis_fee');
                    //$pay_fee = $pay_fee + $added_services_fee * $input['product_nums'];
                    //$input['added_service'] = implode(",", $input['select_added_servce']);
                    $added_service_money = $added_services_fee * 1;
                }

                for ($i = 1; $i <= $activity->service_times; $i++) {

                    if(!empty($medical_special_desc)){
                        $orderNoArr['medical_special_desc'] = $medical_special_desc[0]['desc'];
                    }

//                    dd($medical_special_desc[0]['desc']);

                    $orderNoArr['order_no'] = $modelInfo->order_no . '-' . $i;
                    $orderNoArr['current_fee'] = $activity->presale_per_fee;
                    $orderNoArr['shangmen_fee'] = round($modelInfo->shangmen_fee / $activity->service_times,2);
                    $orderNoArr['product_info'] = $productInfo;

                    $insertSub = $this->orderRep->formartSub($request->all(), $patient, $orderNoArr,"" , $is_white);
                    $insertSub['medical_special_desc'] = "" ;
                    $childorder = $this->orderRep->store($insertSub);
                    $childorder->medical_special_desc = $medical_special_desc[0]['desc'];

                    if($request->input('haocai_need') == 1){
                        $haocai_info =  $request->input('haocai_info');
                        $haocai_info = json_decode($haocai_info,true);
                        $data = array();
                        $datainfo = array();
                        $haocai_price = 0.00;
                        foreach ($haocai_info as $key => $val){
                            if($val['product_id'] == $childorder->product_id){
                                foreach ($val['haocai_info'] as $k1 => $v1){

                                    if($i == $activity->service_times){
                                        $data['order_id'] = $childorder->id;
                                        $data['product_id'] = $val['product_id'];
                                        $data['haocai_id'] = $v1['id'];
                                        $data['haocai_num'] = $v1['buyNum'];
                                        $data['created_at'] = time();
                                        $haocai = ProductHaoCai::where(array('product_id'=>$val['product_id'],'id'=>$v1['id'],'is_del'=>1))->first()->toArray();
                                        $data['haocai_name'] = $haocai['name'];
                                        $data['haocai_price'] = $haocai['price'];
                                        /*add by wj 2019-10-14 增加his字段*/
                                        if(!empty($haocai['xmdm'])){
                                            $data['xmdm'] = $haocai['xmdm'];
                                            $data['sysl'] = $haocai['sysl'];
                                            $data['sapwlh'] = $haocai['sapwlh'];
                                            $data['auto_select'] = $haocai['auto_select'];
                                        }
                                        $datainfo[] = $data;
                                        $haocai_price += $haocai['price'] * $data['haocai_num'];
                                    }
                                }
                            }
                        }

                        OrderHaoCai::insert($datainfo);
                        $childorder->haocai_fee = $haocai_price;
                        //$childorder->order_fee = $childorder->order_fee + $haocai_price;
                        $childorder->pay_fee = $childorder->pay_fee + $haocai_price;
                    }

                    if(!$is_white){

                        //计算增值服务费用
                        $childorder->pay_fee = $childorder->pay_fee + $added_service_money * 1;
                        $childorder->added_service_money = $added_service_money;
                    }else{

                        $childorder->added_service_money = 0;
                    }
                    /*modify by 葛灬先生 2019-02-26 增加product_type_id字段*/
                    $childorder->product_type_id = $productInfo->type_id;
                    //如果支付小于0，则为0.01
                    if ($childorder->pay_fee <= 0)
                        $childorder->pay_fee = 0.01;
                    $childorder->save();

                    //add by Hex@20200623 for 保存意向护士
                    if($request->input('order_nurse_id') && $request->input('order_nurse_name')){
                        $this->saveOrderNurse($childorder,$request->input('order_nurse_id'),$request->input('order_nurse_name'));
                    }
                }
            }
            DB::commit();
            return $this->success($modelInfo);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->fail(100, '数据库操作异常');
        }
    }

    /**
     * @todo 首单优惠  打折
     */
    private function activityType34($request , $modelInfo){

        DB::beginTransaction();
        try{

            //针对需要耗材
            //begin增加耗材详情
            $modelInfo = $this->addHaoCai($request , $modelInfo);
            //end增加耗材详情

            $medical_special_desc = $request->input('medical_special_desc');
            if(!empty($medical_special_desc)){
                $modelInfo->medical_special_desc = $medical_special_desc[0]['desc'];
            }


            //如果支付小于0，则为0.01
            if ($modelInfo->pay_fee <= 0)
                $modelInfo->pay_fee = 0.01;
            $modelInfo->save();

            //add by Hex@20200623 for 保存意向护士
            if($request->input('order_nurse_id') && $request->input('order_nurse_name')){
                $this->saveOrderNurse($modelInfo,$request->input('order_nurse_id'),$request->input('order_nurse_name'));
            }

            DB::commit();
            return $this->success($modelInfo);
        }catch (\Exception $e){
            DB::rollback();
            return $this->fail(100, '增加失败，请重试');
        }
    }

    /**
     * @todo 5优惠券
     */
    private function activityType5($request , $patient , $productInfo , $is_white){

        $activity = $this->ticketRep->getById($request->input('activity_id'));
        if ($activity) {
            if ($activity->member_id != $this->user_id) {
                return $this->fail(103, '优惠券有误');
            }

            $insert = $this->orderRep->formart($request->all(), $this->hospital_id, $this->user_id, $patient, $productInfo, $activity , $is_white);

            if($is_white){

                $insert['activity_id'] = 0;
                $insert['activity_type'] = 0;
                $insert['discount_fee'] = 0;
            }

            DB::beginTransaction();
            try{

                $insert['medical_special_desc'] = "" ;
                $modelInfo = $this->orderRep->store($insert);
                //针对需要耗材

                if($request->input('haocai_need') == 1){
                    $haocai_info =  $request->input('haocai_info');
                    $haocai_info = json_decode($haocai_info,true);
                    $haocai_price = 0.00;
                    foreach ($haocai_info as $key => $val){

                        foreach ($val['haocai_info'] as $k1 => $v1){
                            $haocai = ProductHaoCai::where(array('product_id'=>$val['product_id'],'id'=>$v1['id'],'is_del'=>1))->first()->toArray();
                            $haocai_price += $haocai['price'] * $v1['buyNum'];
                        }

                    }
                    $modelInfo->haocai_fee = $haocai_price;
                    //$modelInfo->order_fee = $modelInfo->order_fee + $haocai_price;
                    $modelInfo->pay_fee = $modelInfo->pay_fee + $haocai_price;
                }

                //begin增加耗材详情
                $modelInfo = $this->addHaoCai($request , $modelInfo);
                //end增加耗材详情


                //判断是否有病情描述
                $medical_special_desc = $request->input('medical_special_desc');
                if(!empty($medical_special_desc)){
                    $modelInfo->medical_special_desc = $medical_special_desc[0]['desc'];
                }
                /*modify by 葛灬先生 2019-02-26 增加product_type_id字段*/
                $modelInfo->product_type_id = $productInfo->type_id;
                //如果支付小于0，则为0.01
                if ($modelInfo->pay_fee <= 0)
                    $modelInfo->pay_fee = 0.01;
                $modelInfo->save();

                //add by Hex@20200623 for 保存意向护士
                if($request->input('order_nurse_id') && $request->input('order_nurse_name')){
                    $this->saveOrderNurse($modelInfo,$request->input('order_nurse_id'),$request->input('order_nurse_name'));
                }

                DB::commit();
                return $this->success($modelInfo);
            }catch (\Exception $e){
                DB::rollback();
                return $this->fail(100, '增加失败，请重试');
            }

        } else {
            return $this->fail(100, '暂无此活动，请核实数据');
        }
    }

    /**
     * @todo 增加耗材详情
     */
    private function addHaoCai($request , $modelInfo){

        //针对需要耗材
        if($request->input('haocai_need') == 1){
            $order_id = $modelInfo->id;
            $haocai_info =  $request->input('haocai_info');
            $haocai_info = json_decode($haocai_info,true);
            $data = array();
            $datainfo = array();
            $haocai_price = 0.00;

            foreach ($haocai_info as $key => $val){
                foreach ($val['haocai_info'] as $k => $v){

                    $data['order_id'] = $order_id;
                    $data['product_id'] = $val['product_id'];
                    $data['haocai_id'] = $v['id'];
                    $data['haocai_num'] = $v['buyNum'];
                    $data['created_at'] = time();
                    $haocai = ProductHaoCai::where(array('product_id'=>$val['product_id'],'id'=>$v['id'],'is_del'=>1))->first()->toArray();
                    $data['haocai_name'] = $haocai['name'];
                    $data['haocai_price'] = $haocai['price'];
                    /*add by wj 2019-10-14 增加his字段*/
                    if(!empty($haocai['xmdm'])){
                        $data['xmdm'] = $haocai['xmdm'];
                        $data['sysl'] = $haocai['sysl'];
                        $data['sapwlh'] = $haocai['sapwlh'];
                        $data['auto_select'] = $haocai['auto_select'];
                    }
                    $datainfo[] = $data;
                    $haocai_price += $haocai['price'] * $v['buyNum'];
                }
            }

            OrderHaoCai::insert($datainfo);
            $modelInfo->haocai_fee = $haocai_price;
        }

        return $modelInfo;
    }

    /**
     * @SWG\Post(path="/order/edit",
     *   tags={"order/edit"},
     *   summary="编辑订单",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *  @SWG\Parameter(
     *     name="order_id",
     *     in="query",
     *     description="订单Id",
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
     *     name="product_id",
     *     in="query",
     *     description="产品Id",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Parameter(
     *     name="give_service",
     *     in="query",
     *     description="赠送服务id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="change_book_time",
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
    public function edit(Request $request)
    {
        $rules = [
            'order_id' => 'required|numeric',
            'patient_id' => 'required|numeric',
            'product_id' => 'required|numeric',
            'give_service' => 'required|numeric',
            'change_book_time' => 'required',
        ];
        $attributes = [
            'order_id' => '订单Id',
            'patient_id' => '患者Id',
            'product_id' => '产品Id',
            'give_service' => '赠送服务',
            'change_book_time' => '服务时间',
        ];

        $validator = Validator::make($request->all(), $rules, [], $attributes);
        $messages = $validator->messages();
        if ($validator->failed()) {
            foreach ($messages->all() as $message) {
                return $this->fail(100, $message);
            }
        }

        $medical_desc = $request->input('medical_desc');
        $medical_special_desc =  $request->input('medical_special_desc');
        $select_desc_imgs = $request->input('select_desc_imgs');
        //$video_list =  $request->input('video_list');

        if(! $medical_desc  && ! $medical_special_desc){
            return $this->fail(100,'请填写病情描述');
        }

        $patient = $this->memberPatientRep->getById($request->input('patient_id'));
        if ($patient->member_id != $this->user_id) {
            return $this->fail(103, '患者有误');
        }
//        $this->checkBookTime($request->input('change_book_time'), $request->input('product_id'));
        $book_time_timestamp = strtotime(str_replace('T', ' ', $request->input('change_book_time')));
        $week = date('N', $book_time_timestamp);
        $hour = date('G.i', $book_time_timestamp);
        // $start = strtotime('2018-02-15 00:00:00');
        // $end = strtotime('2018-02-25 23:59:59');
        // if ($book_time_timestamp >= $start && $book_time_timestamp <= $end) {
        //     return $this->fail(100, '2月15日至2月25日暂不提供服务,请原谅');
        // }
        /*add by wj 2019-01-21 增加假期判断*/
        $rs = HolidayConfig::where(['hospital_id'=>$this->hospital_id,'is_del'=>1])
                ->where('begintime', '<=', $book_time_timestamp)
                ->where('endtime', '>=', $book_time_timestamp)
                ->first();
        if($rs){
            /*modify by wj 2019-02-21 修改提示语*/
            return $this->fail(100,'当前时间不提供服务，请见谅');
        }
        //获取产品可服务时间
        $productModel = new Product();
        $product = $productModel->find( $request->input('product_id'));
        $minHour = 8.30;
        $maxHour = 17.30;
        if($product->service_time){
            $minHour = date('G.i', strtotime(explode(' - ',$product->service_time)[0]));
            $maxHour = date('G.i', strtotime(explode(' - ',$product->service_time)[1]));
        }
        if($product->service_week){
            if(!in_array($week,explode(',',$product->service_week))){
                /*modify by wj 2019-02-21 修改提示语*/
                return $this->fail(100,'当前时间不提供服务，请见谅');
            }
        }
        if (!($hour >= $minHour && $hour <= $maxHour)) {
            /*modify by wj 2019-02-21 修改提示语*/
            return $this->fail(100,'当前时间不提供服务，请见谅');
        }
        if ($book_time_timestamp <= time()) {
            return $this->fail(100, '请选当前之后的时间');
        }

        /*modify by gyy 2019-11-05  增加有些服务是强制上传病情描述图片*/
        $is_need_desc_img = $product->is_need_desc_img;
        //判断病情描述图片是否必填
        if($is_need_desc_img == 1){
            if(!$select_desc_imgs){
                return $this->fail(100,'请上传病情描述的图片');
            }
        }

        $update = $this->orderRep->formartEdit($request->all(),$patient);
        /* modify by wj 2019-10-24 发送护士短信 */
        $orderInfo = $this->model->where(['id'=>$request->input('order_id'),'is_del'=>1])->first();
        $rs = $this->orderRep->update($request->input('order_id'), $update);

        $orderPatient = $this->orderPatientRep->getByAttr(['order_id'=>$request->input('order_id')]);
        //修改订单患者快照表
        $insert = $this->orderPatientRep->formart($rs, $patient);

        if ($orderPatient) {
            $this->orderPatientRep->update($orderPatient->id, $insert);
        } else {
            $this->orderPatientRep->store($insert);
        }

        if ($rs) {
            /*add by wj 2019-10-31 编辑病情描述图片*/
            $OrderDescImg = new OrderDescImg();
            if(!empty($select_desc_imgs)){
                $OrderDescImg->where('order_id', $request->input('order_id'))->delete();
                foreach($select_desc_imgs as $img){
                    $data  = array(
                        "created_at" => time(),
                        "order_id" => $request->input('order_id'),
                        "img_path" => $img,
                    );
                    $OrderDescImg->insert($data);
                }
            }else
                $OrderDescImg->where('order_id', $request->input('order_id'))->delete();
            /* modify by wj 2019-10-24 修改成功发送客服短信 */
            if(empty($orderInfo)){
                return $this->fail(106, '编辑失败,该订单已删除');
            }
            $hospitalInfo = $this->getHospitalInfo($orderInfo['hospital_id']);
            $hospitalInfo = $hospitalInfo["data"];
            $kefu_tel = $hospitalInfo['kefu_tel'] ? explode(',', $hospitalInfo['kefu_tel']) : '';
            $data = array(
                'hospital_name' => $hospitalInfo['hospital_name'],
                'order_no'      => $orderInfo['order_no'],
                'phone'         => $patient->patient_phone
            );
            $smsService = new SmsService();
            if(!empty($kefu_tel)){
                foreach ($kefu_tel as $kf) {
                    $smsService->sendSmsToHosKf($kf, 1, 2, '修改订单通知客服', $data);
                }
            }
            /* add by wj 2019-10-24 发送护士短信、添加用户消息 */
            if(!empty($orderInfo['nurse_id'])){
                $nurseInfo = $this->getNurseInfo($orderInfo['nurse_id']);
                if(!empty($nurseInfo))
                    $smsService->sendSmsToHosKf($nurseInfo['phone'], 1, 2, '修改订单通知客服', $data);
                $messageService = new MessageService();
                $replaceData = [];
                $replaceData['book_time_text'] = $orderInfo['book_time'];
                $productModel = new Product();
                $product = $productModel->getInfo($orderInfo['product_id']);
                $replaceData['product_name'] = $product['product_name'];
                $messageService->add(3, $orderInfo['member_id'], 19, $replaceData, $orderInfo['id'], $orderInfo['hospital_id']);
            }

            return $this->success(['id' => $rs->id], '增加成功');
        } else {
            return $this->fail(106, '增加失败，请重试');
        }
    }


    /**
     * @SWG\Get(path="/order/reasonList",
     *   tags={"order/reasonList"},
     *   summary="订单取消原因",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
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
    public function reasonList()
    {
        $result = config('reason');
        return $this->success($result);
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
        $orderModel = new Order();
        $info = $orderModel->getInfo($request->order_id);
        if ($info) {
            if ($info->status != 17) {
                $data['status'] = 17;
                $data['cancel_reason'] = $request->reason;
                $data['cancel_time'] = time();
                if(in_array($info['activity_type'],[1,2])){
                    $orderIdArr = $info->parent->childrens->pluck('id')->toArray();
                    foreach($orderIdArr as $v){
                        $this->orderRep->update($v,$data);
                    }
                }else {
                    $this->orderRep->update($request->order_id,$data);
                }
                return $this->success($info, '取消成功');
            } else {
                return $this->fail(305, '请勿重复操作');
            }
        } else {
            return $this->fail(304, '暂无次订单，请重试');
        }
    }

    /**
     * @SWG\GET(path="/order/getOrderDetail?order_id={order_id}&token={token}",
     *   tags={"order/getOrderDetail"},
     *   summary="根据订单编号获取订单信息",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="order_id",
     *     in="query",
     *     description="订单编号",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="status",
     *     in="query",
     *     description="订单状态",
     *     required=false,
     *     type="integer"
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
    public function getOrderDetail(Request $request)
    {
        $order_id = $request->input('order_id');
        if (!$order_id) {
            return $this->fail(100, '无订单编号');
        }

        $status = $request->input('status')?:'';
        $order = $this->orderRep->getOrderDetail(['orderId' => $order_id, 'userType' => $this->user_type, 'userId' => $this->user_id, 'status'=>$status]);

        if (empty($order)) {
            return $this->fail(101, '订单不存在');
        } else {
            $haocai = OrderHaoCai::where(array('order_id'=>$order['id'],'is_del'=>1))->get();
            $order['haocai']  = $haocai->toArray();
            //add by Hex @20190428 for 护士端 订单详情 返回订单耗材 拼接数据
            $order['order_haocai_name'] = $haocai->isNotEmpty()?implode(',', array_pluck($haocai, 'full_name')):'';
            //end

            $order['order_nurses'] = $order->orderNurse->isNotEmpty()?implode(',', array_pluck($order->orderNurse, 'nurse_name')):'';

            //增值服务
            $AddedService = new AddedService();
            $added_service = $order['added_service'];

            if($added_service){
                $added_service = explode(",", $added_service);
                $select_service  = $AddedService->whereIn("id", $added_service)->get();
                $order['select_service'] = $select_service;
            }
            if(!empty($order['sub_price'])){
                $order['sub_price'] = \GuzzleHttp\json_decode($order['sub_price']);
            }
            if ($order['product_type_id']){
                $producttypeModel = new ProductType();
                $producttype = $producttypeModel->getInfo($order['product_type_id']);
                $order['product_type_name'] = $producttype['type_name'];
            }else{
                $order['product_type_name'] = "";
            }
            //增加产品护士
            $productNurseModel = new ProductNurse();
            $productNurse = $productNurseModel->where(['product_id'=>$order['product_id'],'is_del'=>1])->first();
            if (isset($productNurse['nurse_ids']) && $productNurse['nurse_ids']){
                $order['product_nurse_ids'] = $productNurse['nurse_ids'];
            }

            return $this->success($order);
        }
    }

    public function checkBookTime($change_book_time, $product_id)
    {
        $book_time_timestamp = strtotime($change_book_time . ':00');
        $week = date('N', $book_time_timestamp);
        $hour = date('G.i', $book_time_timestamp);

        //获取产品可服务时间
        $productModel = new Product();
        $product = $productModel->find($product_id);
        $minHour = 8.30;
        $maxHour = 17.30;
        if($product->service_time){
            $minHour = date('G.i', strtotime(explode(' - ',$product->service_time)[0]));
            $maxHour = date('G.i', strtotime(explode(' - ',$product->service_time)[1]));
        }
        if($product->service_week){
            if(!in_array($week,explode(',',$product->service_week))){
                return $this->fail(100, '当前时间不提供服务');
            }
        }
        if (!($hour >= $minHour && $hour <= $maxHour)) {
            return $this->fail(100, '当前时间不提供服务');
        }
        if ($book_time_timestamp <= time()) {
            return $this->fail(100, '请选当前之后的时间');
        }
    }

    public function getNurseOrder(Request $request)
    {
        $order = Order::where(['status'=>3,'is_del'=>1,'delete_flag'=>2])->get();
        return $this->success($order->count());
    }

    public function getOrderMemberIds()
    {
        /** modify by wj 2019-1-10*/
        /* 修改接口名 */
        $member_ids = Order::where(['is_del'=>1])->groupBy('member_id')->pluck('member_id');
        return $this->success($member_ids);
    }

    public function nurseNewOrder(Request $request)
    {
        if(!$request->input('token')){
            return $this->fail(100, '缺少参数');
        }
        $array = array();
        $nurse = curlGet(env('APP_USER_URL').'/nurse/pageData?nurse_id='.$this->user_id.'&page=1&pageRows=999');
        $nurse = \GuzzleHttp\json_decode($nurse,true);
        if($nurse['code'] == 200 && !empty($nurse['data']['data'])){
            $nurse = $nurse['data']['data'][0];
            $array['status'] = $nurse['status'];
            $array['verify_status'] = $nurse['verify_status'];
            $array['credentials'] = $nurse['credentials'];
            $array['nurse_no'] = $nurse['nurse_no'];
        }

        $status = array(5);
        $order_list = $this->model->where(["nurse_id"=>$this->user_id,"is_del"=>1])->whereIn("status",$status)->get();

        $total = $this->model
            ->where('delete_flag', '=', 2)
            ->where('is_del','=',1)
            ->where('nurse_id', '=', $this->user_id)
            ->where('status','=',5)
            ->orderBy('book_time', 'ASC')
            ->count();
        if(!$order_list->isEmpty() || $total > 0){
            $array['has_new'] = 1;
        }else{
            $array['has_new'] = 0;
        }

        return $this->success($array);
    }

    public function checkNursePer(Request $request)
    {
        $nurse = curlGet(env('APP_USER_URL').'/nurse/getNursePaidan?id='.$this->nurse_id);
        $nurse = \GuzzleHttp\json_decode($nurse,true);
        if($nurse['code'] == 200){
            $is_paidan = $nurse['data'];
            if($is_paidan == 1){
                $result = curlGet(env('APP_USER_URL').'/bnurse/getChargeHospitalId?token='.$request->input('token'));
                $result = \GuzzleHttp\json_decode($result,true);
                $hospital_ids = $result['data'];
                $db_order = $this->model->whereIn('hospital_id',$hospital_ids)->where(['status'=>3,'is_del'=>1,'delete_flag'=>2])->count();
                return $this->success(['is_paidan' => 1,'db_order' => $db_order]);
            }else{
                return $this->success(['is_paidan' => 2],'无权限');
            }
        }else
            return $this->fail(100, '获取用户信息失败');
    }

    public function payAccount(Request $request)
    {
        /* add by wj 2019-11-29 江门回写金额*/
        $order_id = $request->input('order_id');
        $account = $request->input('account');
        if(empty($order_id) || empty($account) || !is_numeric($account))
            return $this->fail(100, '参数不合法');
        $info = $this->model->where(["order_no"=>$order_id,"is_del"=>1])->first();
        if(empty($info))
            return $this->fail(100, '订单不存在');
        $rs = $this->model->where(["order_no"=>$order_id])->update(['jm_pay_price' => $account]);
        if($rs)
            return $this->success();
        return $this->fail(100, '更新失败,已更新');
    }

    /**
     * 保存意向护士
     * @param $nurseIds
     * @param $nurseNames
     */
    public function saveOrderNurse($order,$nurseIds,$nurseNames)
    {
        $idArr = explode(',',$nurseIds);
        $nameArr = explode(',',$nurseNames);
        foreach ($idArr as $key => $val) {
            $insert = ['order_id' => $order->id, 'nurse_id' => $val, 'nurse_name'=>$nameArr[$key],'created_at' => time()];
            $insertArr[] = $insert;
        }
        OrderNurse::insert($insertArr);
    }

     /**
      * 获取意向护士
      * @param $nurseIds
      * @param $nurseNames
      */
    public function getOrderNurses(Request $request)
    {
        $order_id = $request->input('order_id');
        if(empty($order_id))
            return $this->fail(100, '参数不合法');
        $info = $this->model->where(["id"=>$order_id,"is_del"=>1])->first();
        if(empty($info))
            return $this->fail(100, '订单不存在');
        $nurse_names = $info->orderNurse->isNotEmpty()?array_pluck($info->orderNurse, 'nurse_name'):[];
        return $this->success($nurse_names);
    }

    /**
     * 获取虚拟号
     * @param Request $request
     */
    public function getDetailPhone(Request $request)
    {
        $nurse_id = $request->input('nurse_id');
        $phone = $request->input('phone');
        $vm_phone = '';
        if(!empty($nurse_id)) {
            $nurse_info = $this->getNurseInfo($nurse_id);
            if(!empty($nurse_info))
                $vm_phone = $this->getVmphone($nurse_info['phone'], $phone);
        }else{
            if($this->user_type == 2){
                $vm_phone = $this->getVmphone($this->sessionUser['phone'], $phone);
            }
        }
        return $this->success($vm_phone);
    }

    private function getVmphone($phone, $phone1)
    {
        $rs = Xiaozhi::instance()->transferforsp($phone, $phone1);
        if($rs) {
            //虚拟号绑定客服号
            Xiaozhi::instance()->bind('17551048825', $rs);
            return $rs;
        }
        return '';
    }

    public function cert(Request $request)
    {
        $order_id = $request->input('order_id');
        $cert_data = $request->input('cert_data');
        if(empty($order_id) || empty($cert_data))
            return $this->fail(100, '参数不合法');
        $info = $this->model->where(["id"=>$order_id,"is_del"=>1])->first();
        if(empty($info))
            return $this->fail(100, '订单不存在');
        $info->cb_cert_text = $cert_data;
        $rs = $info->save();
        if($rs)
            return $this->success();
        return $this->fail(100, '更新失败');
    }
}
