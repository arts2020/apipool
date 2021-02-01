<?php

namespace App\Http\Controllers\V1;

use App\Repositories\UserAssetRepository;
use Illuminate\Http\Request;

class LinkServiceController extends ApiController
{
    protected $link;
    protected $assetRep;

    public function __construct(Request $request,UserAssetRepository $assetRepository)
    {
        parent::__construct($request);
        $this->link = config('link');
        $this->assetRep = $assetRepository;
    }


    public function sendTransaction(Request $request)
    {
        $to = $request->input('to');
        $mount = $request->input('mount');
        if (!$to || !$mount) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数']);
        }
        //查询用户是否存在钱包数据
        $assetInfo = $this->assetRep->getAssetInfo($this->user_id);
        if ($assetInfo) {
            $address = $assetInfo->address;
            return curlLinkPost($this->link['url'] . '/sendTransaction', compact('address', 'to', 'mount'));
        }else{
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数']);
        }
    }


    public function getBalance(Request $request)
    {
        $address = $request->input('address');
        if (!$address) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数']);
        }
        return curlLinkPost($this->link['url'].'/getBalance',compact('address'));
    }

    /**
     * 私钥导入
     */
    public function privateWallter(Request $request)
    {
        $privatekey = $request->input('privatekey');
        if (!$privatekey) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数']);
        }
        return curlLinkPost($this->link['url'].'/privateWallter',compact('privatekey'));
    }

    /**
     * 助记词导入
     */
    public function mnemonicWallter(Request $request)
    {
        $words = $request->input('words');
        if (!$words) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数']);
        }
        return curlLinkPost($this->link['url'].'/mnemonicWallter',compact('words'));
    }

    public function getWalletValidateAddress(Request $request)
    {
        $address = $request->input('address');
        if (!$address) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数']);
        }
        return curlLinkPost($this->link['url'].'/getWalletValidateAddress',compact('address'));
    }
}