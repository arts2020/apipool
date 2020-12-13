<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 17/8/24
 * Time: 下午6:44
 */

namespace App\Http\Services;

use Illuminate\Support\Facades\Redis;

class CacheService
{
    protected static $redis ;

    public function __construct()
    {
        self::$redis = Redis::connection('default');
    }
    /**
     * 设置缓存
     */
    public static function set($key, $data, $expire = '36000'){
        $data =  \GuzzleHttp\json_encode($data);
        self::$redis->set($key, $data);
        self::$redis->expire($key, $expire);
    }
    /**
     * 获取缓存
     */
    public static function get($key){
        $data = self::$redis->get($key);
        if($data)
            $data = \GuzzleHttp\json_decode($data,true);
        return $data;
    }
    /**
     * 删除缓存
     */
    public static function del($key){
        return self::$redis->del($key);
    }

}
