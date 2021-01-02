<?php

namespace App\Http\Controllers\V1;

use App\Repositories\ConfigRepository;
use Illuminate\Http\Request;

class ConfigController extends ApiController
{
    protected $configRep;

    public function __construct(Request $request, ConfigRepository $configRepository)
    {
        parent::__construct($request);
        $this->configRep = $configRepository;
    }

    /**
     * @SWG\Post(path="/getRate",
     *   tags={"getRate"},
     *   summary="汇率配置",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Config"))
     * )
     */
    public function getRate(Request $request)
    {
        $res = $this->configRep->getConfigByKey();

        return $this->success($res);

    }
}