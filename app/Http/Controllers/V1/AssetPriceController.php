<?php

namespace App\Http\Controllers\V1;

use App\Repositories\AssetPriceRepository;
use Illuminate\Http\Request;

class AssetPriceController extends ApiController
{
    protected $assetPriceRep;

    public function __construct(Request $request, AssetPriceRepository $assetPriceRepository)
    {
        parent::__construct($request);
        $this->assetPriceRep = $assetPriceRepository;
    }

    /**
     * @SWG\Post(path="/getAssetprice",
     *   tags={"getAssetprice"},
     *   summary="行情",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Product"))
     * )
     */
    public function getAssetprice(Request $request)
    {
        $lists = $this->assetPriceRep->getList();
        return $this->success($lists);

    }
}