<?php
/**
 * 患者端----产品管理
 * User: alan
 * Date: 2017/8/22
 * Time: 11:15
 */

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Models\OrderComment;
use App\Models\Product;
use App\Models\Log;
use App\Models\ProductRemember;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\Order;
use App\Models\HomeNav;

class ProductController extends ApiController
{
    public $model ;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->model = new Product();
    }

    /**
     * @SWG\Get(path="/product/getProductsByCateId?category_id={category_id}&token={token}",
     *   tags={"product/getProductsByCateId"},
     *   summary="根据产品分类Id获取产品列表",
     *   description="",
     *   operationId="getProductsByCateId",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="category_id",
     *     in="path",
     *     description="产品分类Id",
     *     type="integer",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="path",
     *     description="token访问唯一凭证",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Product"))
     * )
     */
    public function getProductsByCateId(Request $request)
    {
        // $tag = $request->input('tag');
        // $hospitalModel = new \App\Models\Hospital();
        // if($tag != ''){
        //     $hospital = $hospitalModel->selectOne(['tag'=>$tag]);
        //     $this->hospital_id = $hospital['id'];
        //     if(empty($hospital)){
        //         return $this->fail(100,'医院不存在');
        //     }
        // }

        //$this->hospital_id = $hospital['id'];
        if(empty($this->hospital_id)){
            return $this->fail(100,'医院不存在');
        }

        $category_id = $request->input('category_id');
        if(!$category_id){
            return $this->fail(100,'缺少产品分类ID');
        }
        
        $return = [];
        $where = [];
//        $where['is_show'] = 1 ;
        $where['parent_id'] = 0;
        $where['type_id'] = 0;
        $where['category_id'] = $category_id;
        $where['hospital_id'] = $this->hospital_id;
        $where['is_del'] = 1;
        $products = $this->model->where($where)->whereIn('is_show',[1,3])->orderBy('is_show','ASC')->get();
        $return['products'] = $products ;

        $categoryModel = new \App\Models\ProductCategory();
        $category = $categoryModel->getInfo($category_id);
        $return['category_name'] = $category['category_name'];

        return $this->success($return);
    }

    /**
     * @SWG\GET(path="/product/getQuestions",
     *   tags={"product/getQuestions"},
     *   summary="根据产品Id获取常见问题",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="product_id",
     *     in="query",
     *     description="产品Id",
     *     required=true,
     *     type="string",
     *     default=""
     *   ),
     *   @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     description="页码",
     *     type="integer",
     *     required=true,
     *     default=1
     *   ),
     *   @SWG\Parameter(
     *     name="pageRows",
     *     in="query",
     *     description="每页条数",
     *     type="integer",
     *     required=true,
     *     default=15
     *   ),
     *   @SWG\Response(response=200, description="发送成功", @SWG\Schema(ref="#/definitions/Product"))
     * )
     */
    public function getQuestions(Request $request){
        $product_id = $request->input('product_id');
        if(!$product_id){
            return $this->fail(100,'缺少参数');
        }
        $where = [];
        $where['is_del'] = 1;
        $where['product_id'] = $product_id ;
        $questionModel = new Question();
        $pageRows = $request->input('pageRows');
        $page = $request->input('page');
        $result = $questionModel->where($where)->orderBy('created_at','DESC')->paginate($pageRows);

        $result->appends([
            'product_id'=> $product_id,
            'page' => $page,
            'pageRows' => $pageRows,
        ]);

        return $this->success($result);
    }

    /**
     * @SWG\GET(path="/product/getComments",
     *   tags={"product/getComments"},
     *   summary="根据产品Id获取评论",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="product_id",
     *     in="query",
     *     description="产品Id",
     *     required=true,
     *     type="string",
     *     default=""
     *   ),
     *   @SWG\Parameter(
     *     name="tags",
     *     in="query",
     *     description="标签",
     *     type="integer",
     *     required=false,
     *     default=""
     *   ),
     *   @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     description="页码",
     *     type="integer",
     *     required=true,
     *     default=1
     *   ),
     *   @SWG\Parameter(
     *     name="pageRows",
     *     in="query",
     *     description="每页条数",
     *     type="integer",
     *     required=true,
     *     default=15
     *   ),
     *   @SWG\Response(response=200, description="发送成功", @SWG\Schema(ref="#/definitions/Product"))
     * )
     */
    public function getComments(Request $request){
        $product_id = $request->input('product_id');
        if(!$product_id){
            return $this->fail(100,'缺少参数');
        }
        $where = [];
        $where['is_del'] = 1;
        $where['product_id'] = $product_id ;
        $tags = $request->input('tags');
        if($tags && $tags != '全部'){
            $tags = explode(',',$tags);
        }

        $orderCommentModel = new OrderComment();
        $pageRows = $request->input('pageRows');
        $page = $request->input('page');

        $result = $orderCommentModel->where($where)->when(count($tags)>0 && is_array($tags),function ($query) use ($tags){
            $orWhere = '1=1 and ( ';
            foreach ($tags as $tag){
                if($tag != '全部')
                    $orWhere .= " (tags like '%".$tag."%') or";
            }
            $orWhere = substr($orWhere,0,-2);
            $orWhere .= ')';
            return $query->whereRaw($orWhere);
        })->orderBy('created_at','DESC')->paginate($pageRows);


        $result->appends([
            'product_id'=> $product_id,
            'page' => $page,
            'pageRows' => $pageRows,
        ]);
        return $this->success($result);
    }


    /**
     * @SWG\GET(path="/product/addProductRemember",
     *   tags={"product/addProductRemember"},
     *   summary="记住服务保障",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Parameter(
     *     name="product_id",
     *     in="query",
     *     description="产品Id",
     *     required=true,
     *     type="string",
     *     default=""
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="query",
     *     description="token",
     *     type="integer",
     *     required=true,
     *     default=15
     *   ),
     *   @SWG\Response(response=200, description="发送成功", @SWG\Schema(ref="#/definitions/Product"))
     * )
     */
    public function addProductRemember(Request $request){
        $product_id = $request->input('product_id');
        if(!$product_id) {
            return $this->fail(100, '缺少参数');
        }

        if ($this->user_id <= 0){
            return $this->fail(100, '用户不存在');
        }

        $productRememberModel = new ProductRemember();
        $total = $productRememberModel->where(['product_id'=>$product_id,'member_id'=>$this->user_id])->count();
        if ($total > 0){
            return $this->fail(100,'产品已经添加');
        }

        $data = [];
        $data['product_id'] = $product_id;
        $data['member_id'] = $this->user_id;
        $data['created_at'] = time();
        $productRememberModel = new ProductRemember();
        $rs = $productRememberModel->insert($data);

        return $this->success($rs);
    }

    public function getProducts(Request $request)
    {
        $tags = $request->input('tags');
        $ids = $request->input('ids');
        if(!$tags || !$ids) {
            return $this->fail(100, '缺少参数');
        }
        $hids = explode(',', $ids);

        $data = [];
        foreach ($hids as $hid ){
            //产品分类
            $productCategoryModel = new ProductCategory();
            $categories = $productCategoryModel->productCategory($hid);
            $productModel = new Product();
            $cates = [];
            if(!empty($categories)){
                foreach ($categories as $key => $cate){
                    $where = [];
                    $where['parent_id'] = 0;
                    $where['hospital_id'] = $hid;
                    $where['category_id'] = $cate['id'];
                    $where['is_del'] = 1;
                    $products = $productModel->where($where)
                        ->where("is_hot",1)
                        ->where('parent_id',0)
                        ->where('type_id',0)
                        ->whereIn('is_show',[1,3])
                        ->selectRaw('id,product_name,product_shortname,is_show,product_fee')
                        ->limit(5)
                        ->get()
                        ->toArray();
                    foreach ($products as &$value) {
                        if(empty($value['product_shortname']))
                            $value['product_name'] = $value['product_name'];
                        else
                            $value['product_name'] = $value['product_shortname'];
                    }
                    $cate['products'] = $products;
                    $count = $productModel->where($where)->whereIn('is_show',[1,3])->count();
                    if($count>5){
                        $cate['isMore'] = 1;
                    }
                    if($count==0){
                        unset($categories[$key]);
                    }else{
                        array_push($cates,$cate);
                    }
                }
            }
            $data['cate'][$hid] = $cates;
        }

        $data['pro'] = $this->getFootProducts($tags,$hids);
        return $this->success($data);
    }

    /**
     * 仅供小B首页特别推荐使用
     */
    public function getFootProducts($tags,$hospital_id)
    {
        $start_time = strtotime( date("Y-m-01").' 00:00:00');
        $end_time = time();
        //从统计数据取前5
        $logModel = new Log();
        $rs = [];
        $rs = $logModel->getStatistics($tags,$start_time,$end_time);

        //从下单数据取前5
        $where = [];
        $where['is_del'] = 1;
        $where[]=['created_at','>=',strtotime('-2month')];
        $where[]=['created_at','<=',$end_time];
        $order = Order::where($where)->whereIn('hospital_id',$hospital_id)->whereIn('status',[3,5,7,9,11,13,15])->selectRaw('count(*) as pcount,product_id')->groupBy('product_id')->orderBy('pcount','desc')->limit(5)->pluck('product_id')->toArray();

        $rs = array_merge($rs,$order);
        $rs = array_unique($rs);
        $rs = array_slice($rs,0,5);

        //过滤未显示的产品ID
        $rs = Product::whereIn('id',$rs)->where("is_show",1)->where('is_del',1)->pluck('id')->toArray();

        //不足5个，则随机取出几个产品
        $num = count($rs);
        if($num < 5){
            $needNum = 5 - $num;
            $productids = Product::whereIn('hospital_id', $hospital_id)->whereNotIn("id", $rs)->where("is_show",1)->where('is_del',1)->limit($needNum)->pluck('id')->toArray();
            $rs = array_merge($rs,$productids);
        }

        $products = [];
        if(!empty($rs)){
            $products = Product::whereIn('id',$rs)->where("is_show",1)->where('is_del',1)->get();
        }
        
        $userApi = env('USER_API');
        $hospitals = curlGet($userApi.'/hospital/pageData');
        $hospitals = \GuzzleHttp\json_decode($hospitals,true);
        $hospitals = $hospitals['data']['data'];
        foreach ($products as $key => $val){
            foreach ($hospitals as $hospital){
                if($val['hospital_id'] == $hospital['id']){
                    $products[$key]['tag'] = $hospital['tag'];
                    $products[$key]['hospital_name'] = $hospital['hospital_name'];
                }
            }
            $products[$key]['product_intro'] = preg_replace('/\s/', '', strip_tags($products[$key]['product_intro']));
            $products[$key]['product_intro'] = str_replace('&nbsp;','',$products[$key]['product_intro']);
        }
        return $products;
    }

    public function getGoods(Request $request)
    {
        $q = $request->input('q','');
        if($q){
            $goods_url = env('SHOP_URL').'/api/goods/search?q=' . $q;
        }else{
            $goods_url = env('SHOP_URL').'/api/goods/goodsNurseList';
        }

        $rs = curlGet($goods_url);
        $rs = \GuzzleHttp\json_decode($rs,true);
        if($rs['status'] == 1){
            $goods_list = $rs['result'];
            return $this->success($goods_list);
        }
        return $this->fail();
    }

    public function getProductsByUnion(Request $request)
    {
        $where = [];
        $where['parent_id'] = 0;
        $where['type_id'] = 0;
        $where['is_del'] = 1;
        $where['is_show'] = 1;
        $product_ids = $request->input('product_ids');
        $keyword = $request->input('keyword');
        $page = $request->input('page');
        $pageSize = $request->input('pageSize',10);
        if($keyword)
            $where[] = ['product_name','like','%'.$keyword.'%'];
        if(!$product_ids)
            return $this->fail(100, '缺少参数');
        $products = $this->model->where($where)->whereIn('id',explode(',',$product_ids))
                    ->selectRaw('id,hospital_id,product_name,product_shortname,product_intro,icon_path')
                    ->orderBy('created_at','desc')->paginate($pageSize);
        $userApi = env('USER_API');
        $hospitals = curlGet($userApi.'/hospital/pageData');
        $hospitals = \GuzzleHttp\json_decode($hospitals,true);
        $hospitals = $hospitals['data']['data'];
        foreach ($products as $key => $val){
            foreach ($hospitals as $hospital){
                if($val['hospital_id'] == $hospital['id']){
                    $products[$key]['tag'] = $hospital['tag'];
                    $products[$key]['hospital_name'] = $hospital['hospital_name'];
                }
            }
            $products[$key]['product_intro'] = preg_replace('/\s/', '', strip_tags($products[$key]['product_intro']));
            $products[$key]['product_intro'] = str_replace('&nbsp;','',$products[$key]['product_intro']);
        }
        return $this->success($products);
    }

    public function getProductCategory(Request $request)
    {
        $data = [];
        $apiwhere = [];
        $apiwhere['hospital_id'] = $request->input('hospital_id');
        $productCategoryModel = new ProductCategory();
        $productCategories = $productCategoryModel->select($apiwhere);
        array_push($data,[
            'id'=>'',
            'text'=>'',
        ]);
        if(!empty($productCategories)){
            foreach ($productCategories as $productCategory){
                array_push($data,[
                    'id'=>$productCategory['id'],
                    'text'=>$productCategory['category_name'],
                ]);
            }
        }
        return $this->success($data);
    }

    public function getProByCateId(Request $request)
    {
        $category_id = $request->input('category_id');
        $hospital_id = $request->input('hospital_id');
        if(!$category_id || !$hospital_id){
            return $this->fail(100,'缺少参数');
        }
        
        $data = [];
        $where = [];
        $where['parent_id'] = 0;
        $where['type_id'] = 0;
        $where['category_id'] = $category_id;
        $where['hospital_id'] = $hospital_id;
        $where['is_del'] = 1;
        $products = $this->model->where($where)->whereIn('is_show',[1,3])->orderBy('is_show','ASC')->get();
        array_push($data,[
            'id'=>'',
            'text'=>'',
        ]);
        if(!empty($products)){
            foreach ($products as $pro){
                array_push($data,[
                    'id'=>$pro['id'],
                    'text'=>$pro['product_name'],
                ]);
            }
        }
        return $this->success($data);
    }

    public function getProByIds(Request $request)
    {
        $ids = $request->input('ids');
        if(!$ids){
            return $this->fail(100,'缺少参数');
        }
        $ids = explode(',',$ids);
        $where = [];
        $where['parent_id'] = 0;
        $where['type_id'] = 0;
        $where['is_show'] = 1;
        $where['is_del'] = 1;
        $products = $this->model->with('category')->where($where)->whereIn('id',$ids)->orderBy('is_show','ASC')->get();
        $userApi = env('USER_API');
        $hospitals = curlGet($userApi.'/hospital/pageData');
        $hospitals = \GuzzleHttp\json_decode($hospitals,true);
        $hospitals = $hospitals['data']['data'];
        foreach ($products as $key => $val){
            foreach ($hospitals as $hospital){
                if($val['hospital_id'] == $hospital['id']){
                    $products[$key]['tag'] = $hospital['tag'];
                    $products[$key]['hospital_name'] = $hospital['hospital_name'];
                }
            }
        }
        return $this->success($products);
    }
}
