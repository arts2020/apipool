<?php

namespace App\Http\Controllers\V1;

use App\Repositories\NotifyRepository;
use Illuminate\Http\Request;

class NotifyController extends ApiController
{
    protected $notifyRep;

    public function __construct(Request $request, NotifyRepository $notifyRepository)
    {
        parent::__construct($request);
        $this->notifyRep = $notifyRepository;
    }

    /**
     * @SWG\Post(path="/getNotify",
     *   tags={"getNotify"},
     *   summary="首页通知",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *  @SWG\Parameter(
     *     name="asset",
     *     in="query",
     *     description="首页通知",
     *     required=true,
     *     type="integer"
     *   ),
     *   @SWG\Response(response=200, description="获取成功", @SWG\Schema(ref="#/definitions/Notify"))
     * )
     */
    public function getNotifyList(Request $request)
    {
        $lists = $this->notifyRep->getNoticeList();

        return $this->success($lists);

    }

    /**
     * @SWG\GET(path="/getNoticeInfo?notice_id={notice_id}",
     *   tags={"getNoticeInfo"},
     *   summary="根据编号获取详情",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *     name="notice_id",
     *     in="query",
     *     description="id",
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
    public function getNoticeInfo(Request $request)
    {
        $notice_id = $request->input('notice_id');
        if (!$notice_id) {
            return $this->fail(100, '缺少参数公告编号');
        }

        $info = $this->noticeRep->getById($notice_id);

        if (empty($info)) {
            return $this->fail(101, '数据不存在');
        } else {
            return $this->success($info);
        }
    }

}