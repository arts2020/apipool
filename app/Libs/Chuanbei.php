<?php
/**
 * 川北医学院
 * @author: wj
 * @Date: 2020/07/21
 */
namespace App\Libs;

class Chuanbei{

    //云签名用户授权状态检测
    const CB_CHECK_USER = '/service/ecloud/ecloudSignTsp/signUserStatus.action';
    //云签名用户账号授权
    const CB_CHECK_USER_AUTH = '/service/ecloud/ecloudSignTsp/signUserAuth.action';
    //云签名原文数据提交
    const CB_SUBMIT_SIGN = '/service/ecloud/ecloudSignTsp/submitSignData.action';
    //云签名 p1 签名和时间戳签名
    const CB_GET_P1TSP = '/service/ecloud/ecloudSignTsp/signP1AndTsp.action';

    protected static $instance;
    /**
     * 域名
     * @var string
     */
    protected $host;
    /**
     * Appcode
     * @var string
     */
    protected $appcode;
    /**
     * Appkey
     * @var string
     */
    protected $appkey;
    /**
     * Appsecret
     * @var string
     */
    protected $appsecret;
    /**
     * Headers
     * @var array
     */
    protected $headers = [];

    public function __construct()
    {
        if (env('APP_ENV') == 'local') {
            $this->host = env('CB_TEST_HOST');
            $this->appcode = env('CB_TEST_APPCODE');
            $this->appkey = env('CB_TEST_APPKEY');
            $this->appsecret = env('CB_TEST_APPSECRET');
        } else {
            $this->host = env('CB_PRO_HOST');
            $this->appcode = env('CB_PRO_APPCODE');
            $this->appkey = env('CB_PRO_APPKEY');
            $this->appsecret = env('CB_PRO_APPSECRET');
        }
        $author = $this->appcode."  ".$this->appkey."  ".$this->appsecret."  ";
        $this->headers[] = "Authorization:APPCODE ".$author;
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

    public function submit($data, $orderInfo)
    {
        $url = $this->host.self::CB_SUBMIT_SIGN;
        $params = [];
        $params['phoneNum'] = $data['phone'];
        $params['signData'] = $data['signData'];
        $params['name'] = $data['name'];
        $params['cardNum'] = $data['cardNum'];
        $params['signCode'] = '';
        $rs = httpPost($url,$params,$this->headers);
        if(!empty($rs['result']) && $rs['result']['errorCode'] == 0 && $rs['code'] == 200){
            $businessId = $rs['result']['businessId'];
            $url = $this->host.self::CB_GET_P1TSP;
            $params = [];
            $params['phoneNum'] = $data['phone'];
            $params['businessId'] = $businessId;
            $rs1 = httpPost($url,$params,$this->headers);
            if(!empty($rs1['result']) && $rs1['result']['errorCode'] == 0 && $rs1['code'] == 200){
                $orderInfo->cb_signP1 = $rs1['result']['signP1'];
                $orderInfo->cb_signTsp = $rs1['result']['signTsp'];
                $orderInfo->cb_signCert = $rs1['result']['signCert'];
                $orderInfo->cb_signBusinessId = $rs1['result']['signBusinessId'];
                $orderInfo->save();
                return ['flag'=> true];
            }
            if(in_array($rs1['code'],[403,404,405,406])){
                return ['flag'=> false,'msg'=>$rs['msg']];
            }
        }
        if(in_array($rs['code'],[404,405,406])){
            return ['flag'=> false,'msg'=>$rs['msg']];
        }
    }
}