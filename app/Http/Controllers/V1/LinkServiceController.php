<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;

class LinkServiceController extends ApiController
{
    protected $link;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->link = config('link');
    }


    public function sendTransaction(Request $request)
    {
        $address = $request->input('address');
        $to = $request->input('to');
        $mount = $request->input('mount');
        if (!$address || !$to || !$mount) {
            return $this->apiReturn(['code' => 100, 'msg' => '缺少参数']);
        }
        return curlLinkPost($this->link['url'].'/sendTransaction',compact('address','to','mount'));
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