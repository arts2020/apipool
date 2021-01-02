<?php

namespace App\Http\Controllers\V1;

use App\Repositories\AssetStateRepository;
use Illuminate\Http\Request;

class AssetStateController extends ApiController
{
    protected $assetStateRep;

    public function __construct(Request $request, AssetStateRepository $assetStateRepository)
    {
        parent::__construct($request);
        $this->assetStateRep = $assetStateRepository;
    }

    /**
     * @SWG\Post(path="/getAssetstate",
     *   tags={"getAssetstate"},
     *   summary="行情",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Product"))
     * )
     */
    public function getAssetstate(Request $request)
    {
        $lists = $this->assetStateRep->getList();
        return $this->success($lists);

    }
}