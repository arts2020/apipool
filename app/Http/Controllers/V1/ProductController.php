<?php
namespace App\Http\Controllers\V1;

use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    protected $productRep;

    public function __construct(Request $request, ProductRepository $productRepository)
    {
        parent::__construct($request);
        $this->productRep = $productRepository;
    }

    /**
     * @SWG\Post(path="/getProductList",
     *   tags={"getProductList"},
     *   summary="产品列表",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *  @SWG\Parameter(
     *     name="asset",
     *     in="query",
     *     description="产品分类",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Product"))
     * )
     */
    public function getProductList(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数产品分类']);
        }

        $productLists = $this->productRep->getProductList($asset);

        return $this->success($productLists);

    }

    /**
     * @SWG\GET(path="/getProductInfo?product_id={product_id}&token={token}",
     *   tags={"getProductInfo"},
     *   summary="根据产品编号获取详情",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="product_id",
     *     in="query",
     *     description="产品id",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="path",
     *     description="token访问唯一凭证",
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Order"))
     * )
     */
    public function getProductInfo(Request $request)
    {
        $product_id = $request->input('product_id');
        if (!$product_id) {
            return $this->fail(100, '缺少参数产品编号');
        }

        $info = $this->productRep->getById($product_id);

        if (empty($info)) {
            return $this->fail(101, '产品不存在');
        } else {
            return $this->success($info);
        }
    }

}