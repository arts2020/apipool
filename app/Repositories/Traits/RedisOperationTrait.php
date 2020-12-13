<?php
namespace App\Repositories\Traits;

use Illuminate\Support\Facades\Redis;

trait RedisOperationTrait
{
    /**
     * 设置缓存
     */
    public function set($key, $data, $expire = 5184000){
        $data =  \GuzzleHttp\json_encode($data);
        Redis::set($key, $data);
        Redis::expire($key, $expire);
    }

    /**
     * 获取缓存
     */
    public function get($key){
        $data = Redis::get($key);
        if($data)
            $data = \GuzzleHttp\json_decode($data,true);
        return $data;
    }

    /**
     * 删除缓存
     */
    public function del($key){
        return Redis::del($key);
    }

    /**
     * 判断key是否存在
     */
    public function keyExists($key){
        return Redis::exists($key);
    }

}