<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 17/5/2
 * Time: 上午11:26
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Base extends Model{
    /**
     * 该模型是否被自动维护时间戳
     * @var bool
     */
    public $timestamps = false;
    public $redis;

    public $enableCache = false ;
    public $tableCache = false;
    public function setEnableCache($enableCache = false){
        $this->enableCache = $enableCache ;
    }

    public function getEnableCache(){
        return $this->enableCache ;
    }
    public function setTableCache($tableCache = false){
        $this->tableCache = $tableCache;
    }
    public function getTableCache(){
        return $this->tableCache ;
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->redis = Redis::connection('default');
    }
    /**
     * 统计数量
     *
     */
    public function getCount($where=[]){
        if(empty($where)) return false;
        $where['is_del'] = 1 ;

        return self::where($where)->count();
    }

    /**
     * 分页处理
     * @author alan
     * @param $where
     * @param $field
     * @param string $limit
     * @param string $order_by
     * @param string $group_by
     */
    public function pageData($where=[], $field='*' ,$orderBy=['created_at'=>'desc'],$pageRows='20', $orWhere=[]){
        $where['is_del'] = 1 ;
        $query = self::where($where)->select($field);
        if(!empty($orderBy)){
            foreach($orderBy as $key => $value){
                $query->orderBy($key,$value);
            }
        }
        $rs = $query->paginate($pageRows);
        return $rs;
    }

    /**
     * 选择
     * @author alan
     * @date 2017-05-05
     * @param $where
     */
    public function select($where=[],$orders=[],$offset = 0,$limit = 0){
        $where['is_del'] = 1 ;
        $query = self::where($where);
        if(!empty($orders)){
            foreach($orders as $key => $value){
                $query->orderBy($key,$value);
            }
        }
        if($offset>0){
            $query->skip($offset);
        }
        if($limit > 0){
            $query->take($limit);
        }
        return $query->get();
    }

    /**
     * 根据Id获取一条数据
     * @param $id
     * @return bool
     */
    public function getInfo($id,$field='*'){
        if($id == 0) return false;
        if($this->enableCache && !$this->tableCache){ //从缓存中获取
            $data = $this->cacheGet($id,'getInfo');
        }else{
            $where['id'] = $id;
            $where['is_del'] = 1;
            $data = self::where($where)->select($field)->first();
        }
        return $data ;
    }

    /**
     * @author alan
     * @date 2017-05-04
     * @param $where
     * @return array
     */
    public function selectOne($where, $orders=[]){
        if(empty($where)) return false;
        $query = self::where($where);
        if(!empty($orders)){
            foreach($orders as $key => $value){
                $query->orderBy($key,$value);
            }
        }
        $rows = $query->limit(1)->get()->toArray();
        if(count($rows) == 1){
            return  array_shift($rows);
        }
        return false;
    }

    /**
     * 编辑、添加
     * @author alan
     * @param $data
     * @param int $adminId
     */
    public function addOrEdit($data){
        if(empty($data)) return false ; //如果data为空,则直接返回false
        //删除不必要的参数
        if(isset($data['_token']))
            unset($data['_token']);
        if(isset($data['_url']))
            unset($data['_url']);

        $id = $data['id'];
        if($id == 0){ //添加
            unset($data['id']);
            $this->model->created_at = time();
        }else{ //编辑
            $this->model = $this->model->find($id);
            $this->model->updated_at = time();
        }
        foreach($data as $key=> $value){
            if($value == null) $value = '';
            $this->model->$key = $value ;
        }
        $rs = $this->model->save();
        if($this->enableCache){
            $this->model->cacheReset($id,'getInfo');
        }
        if($rs){
            return $this->success('保存成功',200,'',$this->model->id);
        }else{
            return $this->fail('保存失败');
        }
    }

    /**
     * @author alan
     * @date 2017-05-04
     * @return array
     */
    public function getAll($pk ='id'){
        $where['is_del'] = 1;
        $rs = self::where($where)->get();
        $list = [];
        foreach($rs as &$item){
            $list[$item[$pk]] = $item ;
        }

        return $list;
    }

    /**
     * 删除
     * @author alan
     * @date 2015-05-05
     */
    public function doDrop($ids){
        $rs = self::whereIn('id', $ids)->update(['is_del'=>0]);
        if($rs){
            if(is_string($ids)){
                $ids = explode(',',$ids);
            }elseif(is_numeric($ids)){
                $ids = [$ids];
            }
            if(count($ids) > 0){
                foreach ($ids as $id){
                    $this->cacheDelete($id);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 根据条件删除
     * @author alan
     * @date 2015-05-18
     */
    public function doDropByWhere($where){
        if(empty($where)) return false;
        return self::where($where)->update(['is_del'=>0]);
    }

    /**
     * 物理删除
     * @author alan
     * @date 2015-05-05
     */
    public function doDelete($where){
        if(empty($where)) return false;
        return self::where($where)->delete();
    }

    /**
     * 设置缓存
     */
    public function cacheReset($params, $method_name){
        $this->cacheDelete($params);
        if(method_exists($this, $method_name)){
            $this->setTableCache(true);
            $data = $this->$method_name($params);
        }else{
            return ['code'=>100,'msg'=>'方法不存在~'];
        }
        $this->cacheSet($params, $data);
        return  $data;
    }

    /**
     * 设置缓存
     */
    public function cacheSet($params, $data){
        if(!$params) return false;
        $key = $this->getCacheKey($params);
        return $this->set($key, $data);
    }

    /**
     * 获取缓存
     */
    public function cacheGet($params, $method_name){
        if(!$params) return false;
        $key = $this->getCacheKey($params);
        $data = $this->get($key);
        if(empty($data)){
            $data = $this->cacheReset($params,$method_name);
        }
        if(!empty($data))
            $data = json_decode($data,true);
        return $data;
    }

    /**
     * 删除缓存
     */
    public function cacheDelete($params){
        if(!$params) return false;
        $key = $this->getCacheKey($params);
        return $this->del($key);
    }

    /**
     * 设置key
     */
    public function getCacheKey($params)
    {
        $key = '';
        $key .= $this->getTable();

        if (is_string($params)) {
            $key .= '_' . $params;
        } elseif (is_array($params) && count($params) > 0) {
            $key .= '_' ;
            foreach ($params as $k => $v){
                $key .= $k .'='.$v ;
            }
        } else {
            $key .= '_' . $params;
        }
        return $key;
    }

    /**
     * 设置缓存
     */
    public function set($key, $data, $expire = 5184000){
        $data =  \GuzzleHttp\json_encode($data);
        $this->redis->set($key, $data);
        $this->redis->expire($key, $expire);
    }

    /**
     * 获取缓存
     */
    public function get($key){
        $data = $this->redis->get($key);
        if($data)
            $data = \GuzzleHttp\json_decode($data,true);
        return $data;
    }

    /**
     * 删除缓存
     */
    public function del($key){
        return $this->redis->del($key);
    }

    /**
     * 判断key是否存在
     */
    public function keyExists($key){
        return $this->redis->exists($key);
    }

    /**
     * @Author Yoshiki
     * @Content 根据医院ID查询轮播图
     * @param $hospital_id
     */
    public function carouselFigure($hospital_id) {
        if ($hospital_id == 0){
            return false;
        }

        if ($this->enableCache && !$this->tableCache) {
            $data = $this->cacheGet($hospital_id,'carouselFigure');
        } else {
            $where['hospital_id'] = $hospital_id;
            $where['is_show'] = 1;
            $where['is_del'] = 1;
            $data = self::where($where)->select('*')->orderBy('sort', 'asc')->get();
        }

        return $data;
    }


    /**
     * @Author Yoshiki
     * @Content 根据医院ID查询产品分类
     * @param $hospital_id
     */
    public function productCategory($hospital_id) {
        if ($hospital_id == 0) {
            return false;
        }

        if ($this->enableCache && !$this->tableCache) {
            $data = $this->cacheGet($hospital_id, 'productCategory');
        } else {
            $where['hospital_id'] = $hospital_id;
            $where['is_del'] = 1;
            $where['is_show'] = 1;
            $data = self::where($where)->select('id','category_name', 'category_logo_path')->orderBy('sort', 'asc')->get();
        }

        return $data;
    }


    /**
     * @Author Yoshiki
     * @Content 根据医院ID查询热门分类
     * @param $hospital_id
     */
    public function hotCategory($hospital_id) {
        if ($hospital_id == 0) {
            return false;
        }

        if ($this->enableCache && !$this->tableCache) {
            $data = $this->cacheGet($hospital_id, 'hotCategory');
        } else {
            $where['hospital_id'] = $hospital_id;
            $where['is_hot'] = 1;
            $where['is_show'] = 1;
            $where['is_del'] = 1;
            $data = self::where($where)->select('id','product_name', 'logo_path')->get();
        }

        return $data;
    }

    /**
     * @Author Yoshiki
     * @Content 根据医院ID查询首页消息
     * @param $hospital_id
     */
    public function homeMessage($hospital_id) {
        if ($hospital_id == 0) {
            return false;
        }

        if ($this->enableCache && !$this->tableCache) {
            $data = $this->cacheGet($hospital_id, 'homeMessage');
        } else {
            $where['hospital_id'] = $hospital_id;
            $where['is_show'] = 1;
            $where['is_del'] = 1;
            $data = self::where($where)->select('tip_content')->orderBy('sort', 'asc')->get();
        }

        return $data;
    }

    /**
     * @Author Yoshiki
     * @Content 根据医院ID查询首页超级护理
     * @param $hospital_id
     */
    public function superNursing($hospital_id) {
        if ($hospital_id == 0) {
            return false;
        }

        if ($this->enableCache && !$this->tableCache) {
            $data = $this->cacheGet($hospital_id, 'superNursing');
        } else {
            $where['hospital_id'] = $hospital_id;
            $where['is_show'] = 1;
            $where['is_del'] = 1;
            $data = self::where($where)->select('path', 'title', 'content')->orderBy('sort', 'asc')->get();
        }

        return $data;
    }

    /**
     * @Author Yoshiki
     * @Content 根据医院ID查询产品列表
     * @param $hospital_id
     */
    public function productList($hospital_id) {
        if ($hospital_id == 0) {
            return false;
        }

        if ($this->enableCache && !$this->tableCache) {
            $data = $this->cacheGet($hospital_id, 'productList');
        } else {
            $where['hospital_id'] = $hospital_id;
            $where['is_show'] = 1;
            $where['is_del'] = 1;
            $data = self::where($where)->select('product_name', 'logo_path', 'product_intro')->get();
        }

        return $data;
    }

    /**
     * @Author Yoshiki
     * @Content 根据医院ID及产品ID查询产品介绍
     * @param $hospital_id
     * @param $product_id
     */
    public function productIntroduction($hospital_id, $product_id) {
        if ($hospital_id == 0 || $product_id == 0) {
            return false;
        }

        if ($this->enableCache && !$this->tableCache) {
            $data = $this->cacheGet($hospital_id, 'productIntroduction');
        } else {
            $where['hospital_id'] = $hospital_id;
            $where['id'] = $product_id;
            $where['is_show'] = 1;
            $where['is_del'] = 1;
            $data = self::where($where)->select('*')->first();
        }

        return $data;
    }

    /**
     * @Author Yoshiki
     * @Content 根据医院ID用户ID查询患者列表
     * @param $hospital_id
     */
    public function patientList($hospital_id, $member_id)
    {
        if ($hospital_id == 0 || $member_id == 0) {
            return false;
        }

        if ($this->enableCache && !$this->tableCache) {
            $data = $this->cacheGet($hospital_id, 'patientList');
        } else {
            $where['hospital_id'] = $hospital_id;
            $where['member_id'] = $member_id;
            $where['is_del'] = 1;
            $data = self::where($where)->select('id','patient_id_card', 'patient_name', 'patient_phone', 'patient_gender', 'patient_age','is_default')->get();
        }

        return $data;
    }

    /**
     * 动态格式化时间戳
     */
    public function getCreatedAtAttribute($date)
    {
        return Carbon::createFromTimestamp($date)->toDateTimeString();
    }

    public function batchUpdate(array $update, $whenField = 'id', $whereField = 'id')
    {
        $when = [];
        $ids = [];
        foreach ($update as $sets) {
            #　跳过没有更新主键的数据
            if (!isset($sets[$whenField])) continue;
            $whenValue = $sets[$whenField];

            foreach ($sets as $fieldName => $value) {
                #主键不需要被更新
                if ($fieldName == $whenField) {
                    array_push($ids, $value);
                    continue;
                };

                $when[$fieldName][] = "when '{$whenValue}' then '{$value}'";
            }
        }

        #　没有更新的条件id
        if (!$when) return false;

        $query = $this->whereIn($whereField, $ids);

        #　组织sql
        foreach ($when as $fieldName => &$item) {
            $item = DB::raw("case $whenField " . implode(' ', $item) . ' end ');
        }
        return $query->update($when);
    }
}