<?php
namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderPatient;
use App\Models\Taocan;
use App\Models\Ticket;
use App\Models\Product;
use App\Models\AddedService;
use App\Models\OrderRefund;
use App\Http\Services\OrderService;
use App\Http\Services\SmsService;
use Carbon\Carbon;

class OrderRepository
{
    use BaseRepository;

    protected $model;
    protected $product;
    protected $taocan;
    protected $ticket;

    public function __construct(Order $order, Product $product, Taocan $taocan, Ticket $ticket,
                                OrderService $orderService, SmsService $smsService, OrderVisitRepository $orderVisitRep)
    {
        $this->model = $order;
        $this->product = $product;
        $this->taocan = $taocan;
        $this->ticket = $ticket;
        $this->orderService = $orderService;
        $this->smsService = $smsService;
        $this->orderVisitRep = $orderVisitRep;
    }

    public function getOrderLists($userType, $userId, $hospitalId, $input)
    {
        $where = [];
        $whereIn = false;
        if ($userType == 1) {
            /*add by wj 2019-07-19 增加无tag的查询*/
            if(!empty($input['tag']))
                $where['hospital_id'] = $hospitalId;
            $where['member_id'] = $userId;
            $where['delete_flag'] = 2; //用户删除标记
            $where[] = ['status', '<>', 19]; //去除已删除的订单
        } elseif ($userType == 2 && $input['status'] != 3) {
            $where['nurse_id'] = $userId;
            $where[] = ['status', '<>', 17]; //已取消
            $where[] = ['status', '<>', 19]; //已删除
            $where[] = ['status', '<>', 21]; //退款中
            $where[] = ['status', '<>', 23]; //退款失败
            $where[] = ['status', '<>', 25]; //退款成功
        }
        if (isset($input['patient_id']) && $input['patient_id']){
            $where['patient_id'] = $input['patient_id'];
        }

        $status = $input['status'];
        $pageRows = $input['pageRows'];
        $sort = 'book_time';
        $order = 'desc';
        switch ($status) {
            case 1: //待付款
                $where['status'] = 1;
                break;
            case 2: //待服务
                $whereIn = true;
                $status = [3, 5, 7, 9];
                break;
            case 3: //待派单
                $where['status'] = 3;
                $sort = 'created_at';
                $order = 'desc';
                break;
            case 5:
                $where['status'] = 5; //护士待确定
                $order = 'asc';
                break;
            case 7:
                $whereIn = true;
                $status = [7, 9]; //护士待出发(待出诊)
                $order = 'asc';
                break;
            case 11: //待确认
                $where['status'] = 11;
                $order = 'asc';
                break;
            case 13: //待评价
                $whereIn = true;
                $status = [13, 15];
                break;
        }
        $where['is_del'] = 1;
        $where['delete_flag'] = 2;
        //modify by Hex @20190404 for 返回附属订单数据
        $query = $this->model->with(['hasManySubOrders'=> function($query){
            $query->latest('pay_time');
        },'hasManySubOrders.hasManyProducts','hasManyHaocai'=> function($query){
            $query->IsDel();
        },'product.treatment'=> function($query){
            $query->IsDel();
        }])->where($where);
        if ($whereIn) {
            $query->whereIn('status', $status);
        }
        //如果是从护士端过来，查询该护士的手机号码，判断是否是某个医院的客服
        $result = $query->select('*')
            ->whereRaw("((activity_type in (1,2) and parent_order_no <> '') or activity_type not in (1,2))")
            ->when($userType == 2 && $input['status'] == 3,function ($query1) use ($input) {
                $userApi = env('USER_API');
                $result = curlGet($userApi.'/bnurse/getChargeHospitalId?token='.$input['token']);
                $result = \GuzzleHttp\json_decode($result,true);
                $hospital_ids = $result['data'];
                return $query1->whereIn('hospital_id',$hospital_ids);
            })
            ->when($userType == 2 && $input['status'] == 13,function ($query1){
                return $query1->where('is_nurse_pj',0);
            })
            ->when($userType == 1 && $input['status'] == 13,function ($query1) {
                return $query1->where('is_member_pj',0);
            })
            ->orderBy($sort, $order)
            ->paginate($pageRows);

        $userApi = env('USER_API');

        //针对护士端可以对一级退款订单进项审核
        if($status == 100){
            $result = curlGet($userApi.'/bnurse/getChargeHospitalId?token='.$input['token']);
            $result = \GuzzleHttp\json_decode($result,true);
            $hospital_ids = $result['data'];
            $where = array(
                'order_refund.verify_status'=>0,
                'order_refund.is_del' => 1,
                'order_refund.source' => 1
            );

            $OrderRefund = new OrderRefund();
            $result = $OrderRefund::selectRaw('zpd_order_refund.id as refund_id,zpd_order.*')
                ->leftJoin('order','order_refund.order_id','=','order.id')
                ->where($where)
                ->whereIn('order_refund.hospital_id',$hospital_ids)
                ->whereIn('order.hospital_id',$hospital_ids)
                ->orderBy('order_refund.created_at','desc')
                ->paginate($pageRows);
        }

        foreach ($result as $key => &$value) {

            $hospital = curlGet($userApi . '/hospital/getInfo?id=' . $value->hospital_id);
            $hospital = \GuzzleHttp\json_decode($hospital, true);
            $hospital_name = $hospital['data']['hospital_name'];
            $value['hospital_tag'] = $hospital['data']['tag'];
            $value['hospital_name'] = $hospital_name;
            $value['book_time_text'] = $value->change_book_time ?: $value->book_time;
            $value['order_address'] = $value->change_order_address ?: $value->order_address;
            /*add by wj 2020-01-14 增加宣传话术*/
            $value['has_adv'] = $hospital['data']['info']['adv_words'] ? $hospital['data']['info']['adv_words'] : '';

            //add by Hex@20200622 for 服务中订单是否显示完成按钮
            $value['show_service_user'] = $value['show_service_nurse'] = false;
            if($value->status == 11 && $hospital['data']['info']['show_service']??'')
            {
                $show_service = $hospital['data']['info']['show_service'];
                if(strpos($show_service,'1') !== false){
                    $value['show_service_nurse'] = true;
                }
                if(strpos($show_service,'2') !== false){
                    $value['show_service_user'] =  true;
                }
            }
            /* add by wj 2020-06-29 护理人员*/
            $value['huli_nurse'] = '';
            if(!empty($value->nurse_id)) {
                $nurse = curlGet($userApi . '/bnurse/getInfoByNId?nurse_id=' . $value->nurse_id);
                $nurse = \GuzzleHttp\json_decode($nurse, true);
                if($nurse['code'] == 200)
                    $value['huli_nurse'] = $nurse['data']['realname'];
                else
                    $value['huli_nurse'] = '';
            }

            if ($status == 100) {
                $patient = OrderPatient::where(array('is_del' => 1, 'order_id' => $value->id))->first();
                $value['patient_name'] = !empty($patient) ? $patient->patient_name : '';
                $value['book_time_text'] = date('Y-m-d H:i:s', $value['book_time_text']);
                $value['order_refund'] = 1;
            } else {
                $value['patient_name'] = $value->orderPatient ? $value->orderPatient->patient_name : '';
                $value['order_refund'] = 0;
            }

            $value['product_name'] = $value->product->product_name;
            $value['product_path'] = $value->product->logo_path;
            $value['button_text'] = $this->orderService->getOrderButtonText($value->status);
            $value['order_text'] = $this->model->show_status[$value->status];
            $value['num_intro'] = 'x1';
            if (in_array($value['activity_type'], [1, 2]) && $status != 100) { //1搭配套餐、2预购套餐
                $value['num_intro'] = '搭配套餐：' . explode('-', $value->order_no)[1] . '/' . $this->getTaocanCount($value->parent_order_no);
            }
            $value['created_at_text'] = date("Y-m-d H:i", strtotime($value['created_at']));

            //add by Hex @20190428 for非护士端审核退款订单
            if($status != 100) {
                //add by Hex @20190408 for 追加订单按钮逻辑判断
                $value['has_haocai'] = ! $value['hasManyHaocai']->isEmpty() ? true : false;
                $value['has_zhiliao'] = ! $value['product']['treatment']->isEmpty() ? true : false;
                unset($value['hasManyHaocai']);
                //add by Hex @20190410 for 子订单数据处理
                if (!empty($value['hasManySubOrders'])) {
                    foreach ($value['hasManySubOrders'] as $subk => &$subv) {
                        $subv['status_text'] = $subv->status_text;
                        $subv['sub_order_name'] = implode(',', array_pluck($subv->hasManyProducts, 'full_name'));
                        unset($subv->hasManyProducts);
                    }
                }
            }
            /*2020-07-31 add by wj 增值服务*/
            $AddedService = new AddedService();
            $added_service = $value['added_service'];
            if($added_service){
                $added_service = explode(",", $added_service);
                $select_service = $AddedService->whereIn("id", $added_service)->get();
                $select_service = $select_service->pluck('service_name')->toArray();
                $value['select_service'] = implode('；',$select_service);
            }
        }

        return $result;
    }


    public function getTaocanCount($parent_order_no)
    {
        return $this->model->where('parent_order_no', '=', $parent_order_no)->count();
    }

    /**
     * 创建
     *
     * @param array $input
     * @return \App\Models\Order
     */
    public function store($input)
    {
        return $this->savePost(new $this->model, $input);
    }

    /**
     * 更新
     *
     * @param int $id
     * @param array $input
     * @return \App\Models\Order
     */
    public function update($id, $input)
    {
        $model = $this->model->find($id);
        return $this->savePost($model, $input);
    }

    /**
     * 保存
     *
     * @param Order $order
     * @param  $input
     * @return
     */
    public function savePost($model, $input)
    {
        $model->fill($input);
        $model->save();
        return $model;
    }

    public function formart($input, $hospitalId, $userId, $patientInfo, $productInfo, $activity = '',$is_white = false)
    {
        $input['order_no'] = build_no('T');
        $input['book_time'] = strtotime(str_replace('T', ' ', $input['book_time']));
        $input['patient_phone'] = $patientInfo->patient_phone;
        $input['order_address'] = $patientInfo->province . $patientInfo->street .'。门牌号为：'. $patientInfo->address;
        $input['lng'] = $patientInfo->lng;
        $input['lat'] = $patientInfo->lat;
        $input['member_id'] = $userId;
        $input['hospital_id'] = $hospitalId;
        $input['order_time'] = $input['created_at'] = time();
        $input['status'] = 1;

        //处理科室逻辑
        $this->formatkeshi($input,$patientInfo);


        switch ($input['activity_type']) {
            case 1: //搭配套餐
                $input['order_fee'] = $input['current_fee'] = $activity->total_taocan_fee;
                $pay_fee = $activity->total_taocan_fee;
                break;
            case 2: //预购套餐
                $input['order_fee'] = $input['current_fee'] = $activity->service_fee;
                $pay_fee = $activity->service_fee;
                break;
            case 3: //首单优惠
                $input['order_fee'] = $input['current_fee'] = $productInfo->product_fee;
                $input['discount_fee'] = $activity->first_youhui;
                $pay_fee = $productInfo->product_fee - $activity->first_youhui;
                break;
            case 4: //打折
                $input['order_fee'] = $input['current_fee'] = $productInfo->product_fee;
                $pay_fee = $productInfo->product_fee * ($activity->discount_lidu / 100);
                $input['discount_fee'] = $productInfo->product_fee - $pay_fee;
                break;
            case 5: //优惠券
                $input['order_fee'] = $input['current_fee'] = $productInfo->product_fee;
                $input['discount_fee'] = $activity->ticket_money;
                $pay_fee = $productInfo->product_fee - $activity->ticket_money;
                break;
            default:
                $input['order_fee'] = $input['current_fee'] = $productInfo->product_fee;
                $pay_fee = $productInfo->product_fee;
                break;
        }

        //判断是否是有增值服务
        //增值服务
        $AddedService = new AddedService();

        if($input['select_added_servce']){
            $input['select_added_servce'] = json_decode($input['select_added_servce'] , true);
            $added_services_fee  = $AddedService->whereIn("id",$input['select_added_servce'])->get()->sum('service_dis_fee');

            $pay_fee = $pay_fee + $added_services_fee * $input['product_nums']; 
            $input['order_fee'] = $input['order_fee'] + $added_services_fee * $input['product_nums'];

            $input['added_service'] = implode(",", $input['select_added_servce']);
            $input['added_service_money'] = $added_services_fee ;
               
        }

        if($is_white){
            $pay_fee = 0;
            $input['order_fee'] = 0;
            $input['discount_fee'] = 0;
        }
        
        $input['pay_fee'] = $pay_fee + $input['shangmen_fee'];

        return $input;
    }
    /*
     * 根据病人科室，设定订单所在科室，优先级顺序，校准科室、医院科室、注册科室
     */
    private function formatkeshi(&$input,$patientInfo){

        if ($patientInfo['register_keshi_id']>0){
            $input['keshi_id'] = $patientInfo['register_keshi_id'];
        }

        if ($patientInfo['his_bind_keshi_id']>0){
            $input['keshi_id'] = $patientInfo['his_bind_keshi_id'];
        }

        if ($patientInfo['keshi_id']>0){
            $input['keshi_id'] = $patientInfo['keshi_id'];
        }
    }

    public function formartSub($input, $patientInfo, $orderNoArr, $source = '',$is_white = false)
    {

        $input['order_no'] = $orderNoArr['order_no'];
        $input['parent_order_no'] = $orderNoArr['parent_order_no'];
        $input['order_fee'] = $orderNoArr['product_info']['product_fee'];

        if($is_white){
            $input['order_fee'] = 0 ;
        }

        $input['discount_fee'] = $orderNoArr['product_info']['product_fee'] - $orderNoArr['current_fee'];
        
        if($is_white){

            $input['pay_fee'] = $orderNoArr['shangmen_fee'] ;
            $input['discount_fee'] = 0 ;
        }else{
            $input['pay_fee'] = $orderNoArr['current_fee'] + $orderNoArr['shangmen_fee'];
        }

        $input['current_fee'] = $orderNoArr['current_fee'];
        $input['product_id'] = $orderNoArr['product_info']['id'];
        $input['shangmen_fee'] = $orderNoArr['shangmen_fee'];
        $input['book_time'] = strtotime(str_replace('T', ' ', $input['book_time']));
        $input['order_address'] = $patientInfo->province . $patientInfo->street .'。门牌号为：'.$patientInfo->address;
        $input['lng'] = $patientInfo->lng;
        $input['lat'] = $patientInfo->lat;
        $input['member_id'] = $orderNoArr['user_id'];;
        $input['hospital_id'] = $orderNoArr['hospital_id'];
        $input['patient_phone'] = $patientInfo->patient_phone;
        $input['order_time'] = $input['created_at'] = time();
        $input['status'] = 1;

        //处理科室逻辑
        $this->formatkeshi($input,$patientInfo);
        
        if($input['select_added_servce']){

            $input['select_added_servce'] = json_decode($input['select_added_servce'] , true);
            $input['added_service'] = implode(",", $input['select_added_servce']);
        }

        return $input;
    }

    public function formartEdit($input, $patient)
    {

        $input['order_address'] = $patient->province . $patient->street . $patient->address;
        $input['lng'] = $patient->lng;
        $input['lat'] = $patient->lat;
        $input['change_book_time'] = strtotime(str_replace('T', ' ', $input['change_book_time']));
        $input['status'] = 3;
        $input['nurse_id'] = '';
        $input['medical_desc'] = $input['medical_desc'];
        $input['medical_special_desc'] = $input['medical_special_desc'];

        return $input;
    }

    /*
     * 查询是否有正常状态首单优惠的订单
     */
    public function getFirst($userId)
    {
        $firstOrder = $this->model->where(['member_id' => $userId])->whereRaw("(status <> 17 and status <> 25 and status <> 1)")->get()->toArray();
        if ($firstOrder) {
            $pass = false;
        } else {
            $pass = true;
        }

        return $pass;
    }

    /*
     * 获取订单详情
     */
    public function getOrderDetail($request)
    {
        $info = $this->model->with(['orderPatient', 'orderNurse','hasOrderDescImg' , 'hasOrderDescVideo' , 'product.treatment'=> function($query){
            $query->IsDel();
        }, 'giveService', 'services','hasManySubOrders','hasManySubOrders.hasManyProducts','hasManyHaocai'=> function($query){
            $query->IsDel();
        }])
            ->where(['id' => $request['orderId'], 'is_del' => 1])
            ->when(($request['userType'] == 2 && $request['status'] != 3), function ($query) use ($request) {
                return $query->where('nurse_id', $request['userId']);
            })
            ->when(($request['userType'] == 1), function ($query) use ($request) {
                return $query->where('member_id', $request['userId']);
            })->first();

        if ($info) {
            $info['activity_text'] = array_key_exists($info['activity_type'], $this->model->activity) ? $this->model->activity[$info['activity_type']] : '';
            $info['order_text'] = $this->model->mp_status[$info['status']];
            $info['book_time_text'] = $info['change_book_time'] ?: $info['book_time'];
            $info['order_time'] = date("Y-m-d H:i:s", $info['order_time']);
            $info['button_text'] = $this->orderService->getOrderButtonText($info['status']);
            $info['order_address'] = $info['change_order_address'] ?: $info['order_address'];;
            $info['product']['num_intro'] = 'x1';
            $info['product']['taocan_fee'] = $info->product->product_fee;
            if (in_array($info['activity_type'],[1,2])) { //1搭配套餐、2预购套餐
                $info['product']['num_intro'] = '';
                if (!empty($info['parent_order_no']))
                    $info['product']['num_intro'] = '搭配套餐：' . explode('-', $info['order_no'])[1] . '/' . $this->getTaocanCount($info['parent_order_no']);
                $info['product']['taocan_fee'] = $info->current_fee;
            }
            $info['is_edit'] = (($info['status'] < 9) && !$info['change_book_time']) ? true : false;
            //add by Hex @20190410 for 追加订单按钮逻辑判断
            $info['has_haocai'] = $info['hasManyHaocai']->isNotEmpty()?true:false;
            $info['has_zhiliao'] = $info['product']['treatment']->isNotEmpty()?true:false;
            /*add by wj 2020-01-14 增加宣传话术*/
            $userApi = env('USER_API');
            $hospital = curlGet($userApi . '/hospital/getInfo?id=' . $info['hospital_id']);
            $hospital = \GuzzleHttp\json_decode($hospital, true);
            $info['has_adv'] = $hospital['data']['info']['adv_words'] ? $hospital['data']['info']['adv_words'] : '';
            $info['has_jkm'] = $hospital['data']['info']['is_jkm'] == 1? true : false;
            $info['has_choose_nurse'] = $hospital['data']['info']['is_choose_nurse'] == 1? true : false;
            /* add by wj 2020-07-01 护理人员*/
            $info['huli_nurse'] = '';
            if(!empty($info['nurse_id'])) {
                $nurse = curlGet($userApi . '/bnurse/getInfoByNId?nurse_id=' . $info['nurse_id']);
                $nurse = \GuzzleHttp\json_decode($nurse, true);
                if($nurse['code'] == 200)
                    $info['huli_nurse'] = $nurse['data']['realname'];
                else
                    $info['huli_nurse'] = '';
            }


            //add by Hex@20200622 for 服务中订单是否显示完成按钮
            $info['show_service_user'] = $info['show_service_nurse'] = false;
            if($info->status == 11 && $hospital['data']['info']['show_service']??'')
            {
                $show_service = $hospital['data']['info']['show_service'];
                if(strpos($show_service,'1') !== false){
                    $info['show_service_nurse'] = true;
                }
                if(strpos($show_service,'2') !== false){
                    $info['show_service_user'] =  true;
                }
            }

        }
        return $info;
    }


    public function getHistoryList($request, $userId , $hospital_id , $nurseInfo)
    {

        //获取护士信息
        //$nurseModel = new \App\Models\Nurse;
        //$nurseInfo = $nurseModel->find($userId);

        // $nurseInfo = array(
        //         'hospital_id' => $hospital_id
        //     );

        $begin_time = $request['begin_time'];
        $end_time = $request['end_time'];
        $begin_timestamp = Carbon::parse($begin_time)->timestamp;
        $end_timestamp = Carbon::parse($end_time)->endOfDay()->timestamp;
        //计算日期差值
        $days = Carbon::parse($begin_time)->diffInDays(Carbon::parse($end_time));
        //获取时间区间内的有效订单数据
        $res = $this->model
            ->where(['is_del' => 1, 'delete_flag' => 2])
            ->whereNotIn('status', [19])
            ->whereRaw("((change_book_time > 0 and change_book_time between $begin_timestamp and $end_timestamp) or (book_time between $begin_timestamp and $end_timestamp))")
            ->when($nurseInfo, function ($query) use ($nurseInfo) {
                return $query->whereIn('hospital_id', explode(',', $nurseInfo['hospital_id']));
            })
            ->select('id', 'change_book_time', 'book_time', 'change_order_address', 'order_address', 'status', 'nurse_id', 'product_id', 'created_at')
            ->orderBy('created_at', 'DESC')
            ->get();
        $data = [];

        $allNurseIds = array();
        foreach($res as $r){
            if($r['nurse_id']){
                $allNurseIds[] = $r['nurse_id'];
            }
        }

        $allNurseIds = array_unique($allNurseIds);

        $allNurses = array();
        foreach($allNurseIds as $nurse){

            $allNurses[] = self::getNurseInfo($nurse);
        }

        for ($i = 0; $i <= $days; $i++) {
            $time = Carbon::parse($begin_time)->addDays($i)->toDateString();
            $row = ['date' => $time, 'week' => $this->orderVisitRep->getWeek($begin_time, $i), 'datalist' => [], 'has_data' => false];
            if (count($res) > 0) {
                foreach ($res as $k => $v) {
                    $book_time = $v['change_book_time'] ?: $v['book_time'];

                    if($v['nurse_id']){
                        foreach($allNurses as $n){
                            if($n['id'] == $v['nurse_id']){
                                $v['nurse'] = $n;
                                break;
                            }
                        }
                        //取出护士信息
                        //$v['nurse'] = self::getNurseInfo($v['nurse_id']);
                    }

                    if ($time == Carbon::parse($book_time)->toDateString()) {
                        $row['has_data'] = true;
                        if ($days == 6 || $days == 0) {
                            $row['datalist'][] = [
                                'order_id' => $v['id'],
                                'book_time' => $book_time,
                                'nurse_name' => $v['nurse']['realname'] ?: '',
                                "nurse_id" => $v['nurse_id'],
                                'product_name' => $v['product'] ? $v['product']['product_name'] : '',
                                'order_address' => $v['change_order_address'] ?: $v['order_address'],
                                'order_text' => $this->model->show_status[$v['status']]
                            ];
                        }
                    }
                }
            }
            $data[$i] = $row;
        }

        $result['begin_time'] = $begin_time;
        $result['end_time'] = $end_time;
        $result['list'] = $data;

        return $result;
    }


    /**
     * @todo 根据护士id获取护士信息
     */
    public function getNurseInfo($nurse_id){

        $url = env("APP_USER_URL");
        $url = $url . "/bnurse/getInfoByNId?nurse_id=" . $nurse_id;
        $info = file_get_contents($url);
        $info = json_decode($info , true);

        if($info["code"] != 200){
            
            return false;
        }else{
            
            return $info["data"];    
        }
    }


    /**
     * 获取订单基础数据
     * @param $orderId
     * @return mixed
     */
    public function getOrderInfoForCareRecord($orderId) {
        return $this->model->with(['patient'])->find($orderId);
    }
}
