<?php

namespace App\Http\Controllers\V1;

use App\Repositories\UserAssetRepository;
use App\Repositories\UserTradeRepository;
use Illuminate\Http\Request;

class DrawCoinController extends ApiController
{
    protected $assetRep;
    protected $tradeRep;

    public function __construct(Request $request, UserAssetRepository $assetRepository,
                                UserTradeRepository $tradeRepository)
    {
        parent::__construct($request);
        $this->assetRep = $assetRepository;
        $this->tradeRep = $tradeRepository;
    }


    public function coin(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数币种类型']);
        }
        $amount = $request->input('amount');
        if (!$amount) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数币种数量']);
        }
        if( !is_numeric($amount) ){
            return $this->apiReturn(['code' => 100, 'msg' => '参数错误']);
        }
        //查询用户是否存在钱包数据
        $assetInfo = $this->assetRep->getAssetInfo($this->user_id,$asset);
        if ($assetInfo) {
            $insert = [
                'userid' => $this->user_id,
                'asset' => $asset,
                'amount' => $amount,
                'type' => 5,
                'to_address' => $assetInfo->address,
                'state' => 0
            ];
            $res = $this->tradeRep->store($insert);
            return $this->success($res);
        } else {
            return $this->apiReturn(['code' => 100, 'msg' => '提币失败']);
        }
    }


    /**
     * 获取提币记录
     * @param Request $request
     * @return array|mixed
     */
    public function getCoinList(Request $request)
    {
        $asset = $request->input('asset');
        if (!$asset) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数分类']);
        }
        $list = $this->tradeRep->getTradeListForCoin($this->user_id,$asset);
        return $this->success(compact('list'));

    }
}