<?php

namespace App\Http\Controllers\V1;

use App\Repositories\UserAssetRepository;
use App\Repositories\UserTradeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $assetInfo = $this->assetRep->getAssetDetail($this->user_id,$asset);

        if ($assetInfo) {
            if($assetInfo->profit){
                if($amount > $assetInfo->profit->withdrawal_amount){
                    return $this->apiReturn(['code' => 100, 'msg' => '可提币数量不足，请核实！']);
                }
                $res = DB::transaction(function () use ($request, $asset,$amount,$assetInfo) {
                    //创建提币交易记录
                    $insert = [
                        'userid' => $this->user_id,
                        'asset' => turnAsset($asset),
                        'amount' => $amount,
                        'type' => 5,
                        'to_address' => $assetInfo->address,
                        'state' => 0
                    ];
                    $res = $this->tradeRep->store($insert);

                    //用户可提收益减少
                    $assetInfo->profit->decrement('withdrawal_amount', $amount);

                    return $res;
                });

                return $this->success($res);
            }else{
                return $this->apiReturn(['code' => 100, 'msg' => '暂无可提币收益']);
            }
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