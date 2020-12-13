<?php
/**
 * 晓致虚拟号码
 * @author: wj
 * @Date: 2020/07/30
 */
namespace App\Libs;

class Xiaozhi{

    //商务号绑定
    const XZ_CALL_BIND = '/bindMobile.do';
    //商务号解绑
    const XZ_CALL_UNBIND = '/unbindMobile.do';
    //商务号绑定查询
    const XZ_CALL_SEARCHBIND = '/searchBindMobile.do';
    //商务号外呼
    const XZ_CALL_TRANSFER = '/callTransfer.do';
    //关系虚号获取
    const XZ_CALL_TRANSFERFORSP = '/autoCallTransferForSp.do';
    //关系虚号解绑
    const XZ_CALL_UNTRANSFERFORSP = '/unbindCallTransferForSp.do';

    protected static $instance;
    /**
     * 域名
     * @var string
     */
    protected $host;
    /**
     * Xzid
     * @var string
     */
    protected $xzid;
    /**
     * Xzkey
     * @var string
     */
    protected $xzkey;

    public function __construct()
    {
        if (env('APP_ENV') == 'local') {
            $this->host = env('XZ_TEST_HOST');
            $this->xzid = env('XZ_TEST_XZID');
            $this->xzkey = env('XZ_TEST_XZKEY');
        } else {
            $this->host = env('XZ_PRO_HOST');
            $this->xzid = env('XZ_PRO_XZID');
            $this->xzkey = env('XZ_PRO_XZKEY');
        }
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

    /**
     * 商务号绑定
     * @param $phone 客服号码
     * @param $phone1 虚拟号码
     * @return boolean|string
     */
    public function bind($phone, $phone1)
    {
        $url = $this->host.self::XZ_CALL_BIND;
        $params = [];
        $params['id'] = $this->xzid;
        $params['seqId'] = uniqid();
        $params['timestamp'] = $this->getTime();
        $params['bindMobile'] = $phone;
        $params['virtualMobile'] = $phone1;
        $params['bindTime'] = 0;
        $sign = md5($this->xzkey.$this->xzid.$params['seqId'].$params['timestamp'].$params['bindMobile'].$params['virtualMobile'].$params['bindTime']);
        $params['sign'] = $sign;
        $rs = curlPost($url,http_build_query($params));
        $rs = json_decode($rs,true);
        if($rs['result'] == 0){
            return $rs['virtualMobile'];
        }
        return false;
    }
    /**
     * 商务号解绑
     * @param $phone 客服号码
     * @param $phone 虚拟号码
     * @return boolean
     */
    public function unbind($phone,$phone1)
    {
        $url = $this->host.self::XZ_CALL_UNBIND;
        $params = [];
        $params['id'] = $this->xzid;
        $params['timestamp'] = $this->getTime();
        $params['virtualMobile'] = $phone1;
        $params['bindMobile'] = $phone;
        $sign = md5($this->xzkey.$this->xzid.$params['timestamp'].$params['virtualMobile']);
        $params['sign'] = $sign;
        $rs = curlPost($url,http_build_query($params));
        $rs = json_decode($rs,true);
        if($rs['result'] == 0){
            return true;
        }
        return false;
    }
    /**
     * 查询绑定
     * @param $phone 虚拟号码
     * @return boolean|string
     */
    public function searchbind($phone)
    {
        $url = $this->host.self::XZ_CALL_SEARCHBIND;
        $params = [];
        $params['id'] = $this->xzid;
        $params['timestamp'] = $this->getTime();
        $params['virtualMobile'] = $phone;
        $sign = md5($this->xzkey.$this->xzid.$params['timestamp'].$params['virtualMobile']);
        $params['sign'] = $sign;
        $rs = curlPost($url,http_build_query($params));
        $rs = json_decode($rs,true);
        if($rs['result'] == 0){
            return $rs['value'];
        }
        return false;
    }
    /**
     * 呼叫
     * @param $phone 来源号码
     * @param $phone1 呼转至的号码
     * @return boolean
     */
    public function transfer($phone, $phone1, $phone2)
    {
        $url = $this->host.self::XZ_CALL_TRANSFER;
        $params = [];
        $params['id'] = $this->xzid;
        $params['seqId'] = uniqid();
        $params['timestamp'] = $this->getTime();
        $params['fm'] = $phone;
        $params['vm'] = $phone2;
        $params['tm'] = $phone1;
        $params['bindTime'] = 3;
        $sign = md5($this->xzkey.$this->xzid.$params['seqId'].$params['timestamp'].$params['fm'].$params['vm'].$params['tm'].$params['bindTime']);
        $params['sign'] = $sign;
        $rs = curlPost($url,http_build_query($params));
        file_put_contents(storage_path('logs').'/xz.log','呼叫---'.http_build_query($params).PHP_EOL, FILE_APPEND);
        file_put_contents(storage_path('logs').'/xz.log','呼叫---'.$rs.PHP_EOL, FILE_APPEND);
        $rs = json_decode($rs,true);
        if($rs['result'] == 0){
            return true;
        }
        return false;
    }
    /**
     * 获取虚拟号
     * @param $phone 来源号码
     * @param $phone1 呼转至的号码
     * @return boolean|string
     */
    public function transferforsp($phone, $phone1)
    {
        $url = $this->host.self::XZ_CALL_TRANSFERFORSP;
        $params = [];
        $params['id'] = $this->xzid;
        $params['seqId'] = uniqid();
        $params['timestamp'] = $this->getTime();
        $params['fm'] = $phone;
        $params['tm'] = $phone1;
        $params['bindTime'] = 0;
        $sign = md5($this->xzkey.$this->xzid.$params['seqId'].$params['timestamp'].$params['fm'].$params['tm']);
        $params['sign'] = $sign;
        $rs = curlPost($url,http_build_query($params));
        file_put_contents(storage_path('logs').'/xz.log','获取虚拟号---'.http_build_query($params).PHP_EOL, FILE_APPEND);
        file_put_contents(storage_path('logs').'/xz.log','获取虚拟号---'.$rs.PHP_EOL, FILE_APPEND);
        $rs = json_decode($rs,true);
        if($rs['result'] == 0){
            $res = $this->transfer($phone, $phone1, $rs['virtualMobile']);
            if($res)
                return $rs['virtualMobile'];
        }
        return false;
    }
    /**
     * 解绑虚拟号
     * @param $phone 来源号码
     * @param $phone1 呼转至的号码
     * @param $phone1 解除绑定的虚拟号码
     * @return boolean
     */
    public function untransferforsp($phone, $phone1, $phone2)
    {
        $url = $this->host.self::XZ_CALL_UNTRANSFERFORSP;
        $params = [];
        $params['id'] = $this->xzid;
        $params['timestamp'] = $this->getTime();
        $params['fm'] = $phone;
        $params['tm'] = $phone1;
        $params['vm'] = $phone2;
        $sign = md5($this->xzkey.$this->xzid.$params['timestamp'].$params['fm'].$params['tm'].$params['vm']);
        $params['sign'] = $sign;
        $rs = curlPost($url,http_build_query($params));
        $rs = json_decode($rs,true);
        if($rs['result'] == 0){
            return true;
        }
        return false;
    }
    /**
     * 13位时间戳
     * @return float
     */
    private function getTime()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }
}