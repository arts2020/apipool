<?php

use Carbon\Carbon;

/**
 * 获取随机数
 */
if (!function_exists('getRandom')) {
    function getRandom()
    {
        return rand(100000, 999999);
    }
}

/**
 * 生成单号
 */
if (!function_exists('build_no')) {
    function build_no($prex = '')
    {
        return $prex . date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8).str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT);
    }
}

/**
 * 创建目录
 */
if (!function_exists('mkdirs')) {
    function mkdirs($path, $mode = 0777)
    {
        if (is_dir($path) || @mkdir($path, $mode)) return TRUE;
        if (!mkdirs(dirname($path), $mode)) return FALSE;
        return @mkdir($path, $mode);
    }
}

/**
 * curl GET
 */
if (!function_exists('curlGet')) {
    function curlGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode(['code'=>-1,'msg'=>'接口异常']);
        }
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode == 200){
            return $data;
        }else{
            return json_encode(['code'=>-1,'msg'=>'接口异常']);
        }
    }
}

/**
 * curl Post
 */
if (!function_exists('curlPost')) {
    function curlPost($url, $curl_post = [])
    {
        $ch = curl_init(); //初始化
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);  //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode(['code'=>-1,'msg'=>'接口异常']);
        }
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode == 200){
            return $data;
        }else{
            return json_encode(['code'=>-1,'msg'=>'接口异常']);
        }
    }
}

/**
 * 获取客户端IP地址
 * @return string
 */
if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {
        if (getenv('HTTP_CLIENT_IP')) {
            $client_ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $client_ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR')) {
            $client_ip = getenv('REMOTE_ADDR');
        } else {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }
        return $client_ip;
    }
}

/**
 * 获取服务器端IP地址
 * @return string
 */
if (!function_exists('get_server_ip')) {
    function get_server_ip()
    {
        if (isset($_SERVER)) {
            if ($_SERVER['SERVER_ADDR']) {
                $server_ip = $_SERVER['SERVER_ADDR'];
            } else {
                $server_ip = $_SERVER['LOCAL_ADDR'];
            }
        } else {
            $server_ip = getenv('SERVER_ADDR');
        }
        return $server_ip;
    }
}

if (!function_exists('genToken')) {
    function genToken()
    {
        return md5(uniqid());
    }
}

/**
 * 验证手机号是否正确
 * @author honfei
 * @param number $mobile
 */
if (!function_exists('isMobile')) {
    function isMobile($mobile)
    {
        $isMob = "/^1[3-9]{1}[0-9]{9}$/";
        $isTel = "/^([0-9]{3,4}-)?[0-9]{7,8}$/";

        if (preg_match($isMob, $mobile) || preg_match($isTel, $mobile)) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * @author  alan
 * 把数字1-1亿换成汉字表述，如：123->一百二十三
 * @param [num] $num [数字]
 * @return [string] [string]
 */
if (!function_exists('numToWord')) {
    function numToWord($num)
    {
        $chiNum = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $chiUni = array('', '十', '百', '千', '万', '十', '百', '千', '亿', '十', '百', '千', '万', '十', '百', '千');
        $uniPro = array(4, 8);
        $chiStr = '';

        $num_str = (string)$num;
        $count = strlen($num_str);
        $last_flag = true; //上一个 是否为0
        $zero_flag = true; //是否第一个
        $temp_num = null; //临时数字
        $uni_index = 0;

        $chiStr = '';//拼接结果
        if ($count == 2) {//两位数
            $temp_num = $num_str[0];
            $chiStr = $temp_num == 1 ? $chiUni[1] : $chiNum[$temp_num] . $chiUni[1];
            $temp_num = $num_str[1];
            $chiStr .= $temp_num == 0 ? '' : $chiNum[$temp_num];
        } else if ($count > 2) {
            $index = 0;
            for ($i = $count - 1; $i >= 0; $i--) {
                $temp_num = $num_str[$i];
                if ($temp_num == 0) {
                    $uni_index = $index % 15;
                    if (in_array($uni_index, $uniPro)) {
                        $chiStr = $chiUni[$uni_index] . $chiStr;
                        $last_flag = true;
                    } else if (!$zero_flag && !$last_flag) {
                        $chiStr = $chiNum[$temp_num] . $chiStr;
                        $last_flag = true;
                    }
                } else {
                    $chiStr = $chiNum[$temp_num] . $chiUni[$index % 16] . $chiStr;

                    $zero_flag = false;
                    $last_flag = false;
                }
                $index++;
            }
        } else {
            $chiStr = $chiNum[$num_str[0]];
        }
        return $chiStr;
    }
}

//验证身份证是否有效
function validateIDCard($IDCard)
{
    if (strlen($IDCard) == 18 || strlen($IDCard) == 15) {
        return true;
    } else {
        return false;
    }
}

/**
 * add by Hex @20190403
 * 判断浮点是否相等
 */
if (!function_exists('floatcmp')) {
    function floatcmp($f1, $f2)
    {
        $i1 = bcadd((float)$f1, 0.00, 2);
        $i2 = bcadd((float)$f2, 0.00, 2);;
        return ($i1 == $i2);
    }
}

/**
 * http post请求
 */
if (!function_exists('httpPost')) {
    function httpPost($url, $curl_post = [],$headers){
        $ch = curl_init(); //初始化
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);  //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($curl_post));
        curl_setopt($ch, CURLOPT_TIMEOUT,5);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode(['code'=>-1,'msg'=>'接口异常']);
        }
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode == 200){
            return json_decode($data,true);
        }else{
            return json_encode(['code'=>-1,'msg'=>'接口异常']);
        }
    }
}

/**
 * 获取当前时间
 */
if (! function_exists('now')) {
    function now($tz = null)
    {
        return Carbon::now($tz);
    }
}
