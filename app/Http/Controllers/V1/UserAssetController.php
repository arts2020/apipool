<?php

namespace App\Http\Controllers\V1;

use App\Repositories\AssetPriceRepository;
use App\Repositories\UserAssetRepository;
use App\Repositories\UserTradeRepository;
use Illuminate\Http\Request;

class UserAssetController extends ApiController
{
    protected $assetRep;
    protected $tradeRep;

    public function __construct(Request $request, UserAssetRepository $assetRepository,
                                UserTradeRepository $tradeRepository,AssetPriceRepository $assetPriceRepository)
    {
        parent::__construct($request);
        $this->assetRep = $assetRepository;
        $this->tradeRep = $tradeRepository;
        $this->assetPriceRep = $assetPriceRepository;
    }


    public function userAsset(Request $request)
    {
        $asset = $request->input('asset');
        $address = $request->input('address');
        $amount = $request->input('amount');
        if (!$asset || !$address) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数']);
        }

        if( !is_numeric($amount) ){
            return $this->apiReturn(['code' => 100, 'msg' => '参数错误']);
        }

        //查询用户是否已存在该类型数据
        $userid = $this->user_id;
        $assetInfo = $this->assetRep->getAssetInfo($userid,$asset);
        $data = compact('userid','asset','address','amount');
        if($assetInfo){
            $res = $this->assetRep->savePost($assetInfo,$data);
        }else{
            $res = $this->assetRep->store($data);
        }

        return $this->success($res);
    }

    /**
     * @SWG\Post(path="/getMyAsset",
     *   tags={"getMyAsset"},
     *   summary="我的钱包",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *  @SWG\Parameter(
     *     name="asset",
     *     in="query",
     *     description="分类",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Product"))
     * )
     */
    public function getMyAsset(Request $request)
    {
        $lists = $this->assetRep->getAssetList($this->user_id);
        $total = 0;
        foreach ($lists as $v){
            //获取当前市场行情
            $info = $this->assetPriceRep->getInfoByAsset($v['asset']);
            if ($info)
                $total += priceCalc($v['amount'],'*',priceCalc($info['price_usd'],'/',$info['price_btc'])) ;
        }
        $total_cny = $total?turnCny($total):0;
        return $this->success(compact('total','total_cny','lists'));

    }

    /**
     * 获取交易记录
     * @param Request $request
     * @return array|mixed
     */
    public function getTransferList(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数分类']);
        }
        $total = array_sum(array_pluck($this->tradeRep->getTradeList($this->user_id,$asset,1),'amount'));
        $total_cny = $total?turnCny($total):0;
        $list = $this->tradeRep->getTradeList($this->user_id,$asset);
        return $this->success(compact('total','total_cny','list'));

    }

    public function transfer(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数分类']);
        }
        $amount = $request->input('amount');
        if (!$amount) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数交易数量']);
        }
        $to_address = $request->input('to_address');
        if (!$to_address) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数转账地址']);
        }
        $tx = $request->input('tx');
        if (!$tx) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数交易HASH']);
        }

        $assetInfo = $this->assetRep->getAssetInfo($this->user_id,$asset);

        if ($assetInfo) {

            $insert = [
                'userid' => $this->user_id,
                'asset' => $asset,
                'amount' => $amount,
                'type' => 3,
                'from_address' => $assetInfo->address,
                'to_address' => $to_address,
                'tx' => $tx,
                'state' => 0
            ];
            $res = $this->tradeRep->store($insert);
            return $this->success($res);

        } else {
            return $this->apiReturn(['code' => 100, 'msg' => '转账失败']);
        }
    }

}