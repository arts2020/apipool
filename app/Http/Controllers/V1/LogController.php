<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Models\AppLog;
use Illuminate\Http\Request;


class LogController extends ApiController
{

    /**
     * @SWG\POST(path="/log/add",
     *   summary="增加app的日志",
     *   description="",
     *   operationId="",
     *   produces={ "application/json"},
     *   @SWG\Response(response=200, description="发送成功", @SWG\Schema(ref="#/definitions/Zhubo"))
     * )
     */
   public function addInfo(Request $request){

        $idcard = $request->input("idcard");
        $time = $request->input("time");
        $content = $request->input("content");

        $AppLog = new AppLog();
        $data = array(
            "idcard" => $idcard,
            "time" => date("Y-m-d H:i:s") ,
            "content" => $content
        );

       $AppLog->insert($data);
       return $this->success();
   }
}