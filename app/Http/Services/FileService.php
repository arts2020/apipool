<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 17/8/21
 * Time: 上午9:32
 */
namespace App\Http\Services;

use App\Models\SysSmsLog;
use App\Models\SysSmsPort;

class FileService{

    /**
     * 上传文件
     * @param $file
     * @return array
     */
    public function upload($file){
        $file_name = $file->getClientOriginalName();
        $file_ex = $file->getClientOriginalExtension();

        $newname = md5(uniqid().'-'.$file_name).'.'.$file_ex;
        $upload_path = base_path().'/public/hlylAssets/uploads/'.date('Y-m-d').'/';
        $file->move($upload_path, $newname);
        return ['msg'=>'上传成功','code'=>'200','data'=> env('APP_URL'). '/hlylAssets/uploads/'.date('Y-m-d').'/'.$newname,'name'=>$newname];
    }
}