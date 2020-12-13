<?php
/**
 * 江门市中心医院
 * @author: wj
 * @Date: 2020/10/20
 */
namespace App\Libs;

use App\Models\Order;
use App\Models\OrderSub;
use App\Models\ChargesItem;
use App\Models\HaocaiChargesMid;
use App\Models\OrderHaoCai;
use App\Models\OrderJiangmen;
use App\Models\ProductChargesMid;

class Jiangmen{

    protected  $client;
    private    $wsdl_url;
    protected static $instance;

    public function __construct()
    {
        libxml_disable_entity_loader(false);
        $this->wsdl_url = env('JM_URL');
        $this->client = new \SoapClient($this->wsdl_url);
    }

    /**
     * 初始化
     * @access public
     * @param array $options 参数
     * @return Tree
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    public function add($order_id)
    {
        $where = [];
        $where['id'] = $order_id;
        $where['is_del'] = 1;
        $where['jm_status'] = 1;
        $main_model = new Order;
        $cmodel = new ChargesItem;
        $info = $main_model->with(['product', 'orderPatient'])->where($where)->first();
        $main = array(
            'CardNo'	=> $info['orderPatient']['jiuzhen_no'],
            'DoctorNo'	=> 2918,
            'NurseNo'	=> 2918,
            'OrderNo'	=> $info['order_no']
        );
        //上门费
        $shm_charges = ProductChargesMid::where(['product_id'=>$info['product']['id'],'type'=>3])->first();
        if(!empty($shm_charges)){
            $shmItems = $cmodel->selectOne(['id'=>$shm_charges['charges_id']]);
            $main['ServerName'] = $shmItems['name'];
            $main['Items'] = [['itemCode' => $shmItems['code'],'number' => 1]];
            $this->compactData($main,1);
        }
        //产品服务
        $main['ServerName'] = $info['product']['product_name'];
        $charges_ids = ProductChargesMid::where(['product_id'=>$info['product']['id'],'type'=>1])->pluck('charges_id')->toArray();
        $product_items = [];
        if(!empty($charges_ids)){
            $citems = $cmodel->getCodes($charges_ids);
            foreach ($citems as $value){
                $code = explode(',',$value['code']);
                foreach($code as $c){
                    $product_items[] = array(
                        'itemCode' => $c,
                        'number'   => 1
                    );
                }
            }
        }
        if($product_items){
            $main['Items'] = $product_items;
            $this->compactData($main,2);
        }
        //耗材
        $hc_items = [];
        $mainHaocai = OrderHaoCai::where(['order_id'=>$info['id'],'is_del'=>1])->get();
        if($mainHaocai) {
            foreach ($mainHaocai as $m) {
                $main_charges_ids = HaocaiChargesMid::where('haocai_id', $m['haocai_id'])->pluck('charges_id')->toArray();
                if(!empty($main_charges_ids)){
                    $mainHcItems = $cmodel->getCodes($main_charges_ids);
                    foreach ($mainHcItems as $value) {
                        $code = explode(',',$value['code']);
                        foreach($code as $c){
                            $hc_items[] = array(
                                'itemCode' => $c,
                                'number' => $m['haocai_num']
                            );
                        }
                    }
                }
            }
        }
        if($hc_items){
            $main['Items'] = $hc_items;
            $this->compactData($main,3);
        }
    }

    public function addSub($order_id)
    {
        $where = [];
        $where['id'] = $order_id;
        $where['is_del'] = 1;
        $where['jm_status'] = 1;
        $sub_model = new OrderSub;
        $cmodel = new ChargesItem;
        $info = $sub_model->with(['belongsToOrder'=>function($query){
            $query->with(['product', 'orderPatient']);
        },'hasManyProducts'])->where($where)->first();
        $nurseInfo = $this->getNurseInfo($info['belongsToOrder']['nurse_id']);
        if(!empty($nurseInfo)){
            $sub = array(
                'CardNo' => $info['belongsToOrder']['orderPatient']['jiuzhen_no'],
                'DoctorNo' => $nurseInfo['nurse_no'],
                'NurseNo' => $nurseInfo['nurse_no'],
                'ServerName' => $info['belongsToOrder']['product']['product_name'],
                'OrderNo' => $info['belongsToOrder']['order_no']
            );
            $items = [];
            foreach ($info['hasManyProducts'] as $v) {
                $sub_charges_ids = HaocaiChargesMid::where('haocai_id', $v['product_id'])->pluck('charges_id')->toArray();
                if(!empty($sub_charges_ids)){
                    $hcitems = $cmodel->getCodes($sub_charges_ids);
                    foreach ($hcitems as $value) {
                        $scode = explode(',',$value['code']);
                        foreach($scode as $c){
                            $items[] = array(
                                'itemCode' => $c,
                                'number' => $v['num']
                            );
                        }
                    }
                }
            }
            if($items){
                $sub['Items'] = $items;
                $this->compactData($sub,3,$info['order_no']);
            }
        }
    }

    public function changeNurse($order_id)
    {
        $info = Order::where(['is_del'=>1,'id'=>$order_id])->first();
        $nurseInfo = $this->getNurseInfo($info['nurse_id']);
        if (!empty($nurseInfo)) {
            $data = [
                'OrderNo'   => $info['order_no'],
                'DoctorNo'  => $nurseInfo['nurse_no'],
                'NurseNo'   => $nurseInfo['nurse_no'],
                'OrderDate' => substr($info['created_at'],0,10)
            ];
            $dataXml = $this->toXml($data, 'actionUpdateOrder');
            $rs = $this->postData($dataXml);
            file_put_contents(storage_path('logs').'/jiangmen.log','订单编号--'.$info['order_no'].'--推送数据--'.$dataXml.'--返回结果为'.json_encode($rs).PHP_EOL, FILE_APPEND);
        }else
            file_put_contents(storage_path('logs').'/jiangmen.log','订单编号--'.$info['order_no'].'--无护士工号--未推送'.PHP_EOL, FILE_APPEND);
    }

    private function compactData($data, $type = 2, $order_no = '')
    {
        $data['Items'] = $this->arrayUqiue($data['Items']);
        $dataXml = $this->toXml($data,'setPrescription');
        file_put_contents(storage_path('logs').'/jiangmen.log',$type.'---'.$dataXml.PHP_EOL, FILE_APPEND);
        $rs = $this->postData($dataXml);
        file_put_contents(storage_path('logs').'/jiangmen.log',$type.'---'.json_encode($rs).PHP_EOL, FILE_APPEND);
        if($rs['resultCode'] == 0 && $rs['result']){
            $jiangmen = new OrderJiangmen;
            $jiangmen->type = $type;
            $jiangmen->order_no = $data['OrderNo'];
            $jiangmen->sfid = $rs['result']['Id'];
            if($order_no)
                $jiangmen->hc_order_no = $order_no;
            $jiangmen->save();
            if($order_no){
                $subInfo = OrderSub::where('order_no',$order_no)->first();
                $subInfo->jm_status = 2;
                $subInfo->jm_status_text = json_encode($rs);
                $subInfo->save();
            }else{
                $mainInfo = Order::where('order_no',$data['OrderNo'])->first();
                $mainInfo->jm_status = 2;
                $jm_status_text = $mainInfo['jm_status_text'].';'.json_encode($rs);
                $jm_status_text = ltrim($jm_status_text,';');
                $mainInfo->jm_status_text = $jm_status_text;
                $mainInfo->save();
            }
        }else{
            if($order_no){
                $subInfo = OrderSub::where('order_no',$order_no)->first();
                $subInfo->jm_status_text = json_encode($rs);
                $subInfo->save();
            }else{
                $mainInfo = Order::where('order_no',$data['OrderNo'])->first();
                $jm_status_text = $mainInfo['jm_status_text'].';'.json_encode($rs);
                $jm_status_text = ltrim($jm_status_text,';');
                $mainInfo->jm_status_text = $jm_status_text;
                $mainInfo->save();
            }
        }
    }

    private function arrayUqiue($arr)
    {
        $newArr = array();
        if(empty($arr))
            return $newArr;
        foreach ($arr as $v) {
            if (array_key_exists($v['itemCode'], $newArr)) {
                $newArr[$v['itemCode']]['number'] += $v['number'];
            } else {
                $newArr[$v['itemCode']] = $v;
            }
        }
        return $newArr;
    }
    /**
     * 输出xml字符
     * @param $values
     * @return bool|string
     */
    private function toXml($values,$method)
    {
        if (!is_array($values)
            || count($values) <= 0
        ) {
            return false;
        }
        $header = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.appt.adapter.winning.com/"><soapenv:Header/><soapenv:Body><ser:'.$method.'>';
        $xml = "<data><![CDATA[<request><params>";
        foreach ($values as $key => $val) {
            if (is_array($val)) {
                $xml .= $this->arrayTOxml($val);
            }else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</params></request>]]></data>";
        $last = '</ser:'.$method.'></soapenv:Body></soapenv:Envelope>';
        return $header.$xml.$last;
    }

    private function arrayTOxml($arr)
    {
        $xml = '';
        foreach ($arr as $k => $value) {
            $xml .= '<Items>';
            foreach ($value as $key => $val){
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
            $xml .= '</Items>';
        }
        return $xml;
    }

    private function postData($data)
    {
        try{
            $response = $this->client->__doRequest($data,$this->wsdl_url,'',1,0);
            $response = $this->fromXml($response);
            return $response;
        }catch (\SoapFault $e){
            return false;
        }catch(Exception $e){
            return false;
        }
    }
    /**
     * 将xml转为array
     * @param $xml
     * @return mixed
     */
    private function fromXml($xml)
    {
        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        preg_match('/\<return\>(.*?)\<\/return\>/s', $xml, $matches);
        $xml = preg_replace('/\<return\>|<\/return\>/s','',$matches[0]);
        return json_decode(json_encode(simplexml_load_string(html_entity_decode($xml), 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    private function getNurseInfo($nurse_id)
    {
        $url = env("APP_USER_URL");
        $url = $url . "/bnurse/getInfoByNId?nurse_id=" . $nurse_id;
        $info = curlGet($url);
        $info = json_decode($info , true);
        if($info["code"] != 200){
            return false;
        }else{
            return $info["data"];
        }
    }

}
