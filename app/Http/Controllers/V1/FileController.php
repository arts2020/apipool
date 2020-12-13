<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Services\FileService;
use App\Models\TizhengReport;
use Illuminate\Http\Request;

class FileController extends ApiController
{
    /**
     * @SWG\Post(path="/file/upload",
     *   tags={"file/upload"},
     *   summary="文件上传",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *      description="上传文件",
     *      in="formData",
     *      name="upfile",
     *      required=true,
     *      type="file"
     *   ),
     *   @SWG\Parameter(
     *     name="token",
     *     in="query",
     *     description="",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="上传成功", @SWG\Schema(ref="#/definitions/Nurse"))
     * )
     */
    public function upload(Request $request)
    {
        $file = $request->file('upfile');
        $fileLogic = new FileService();
        $msg = $fileLogic->upload($file);
        return $this->apiReturn($msg);
    }

    /**
     * @SWG\Post(path="/file/base64imgupload",
     *   tags={"file/base64imgupload"},
     *   summary="文件上传-base64",
     *   description="",
     *   operationId="",
     *   produces={ "multipart/form-data"},
     *   @SWG\Parameter(
     *      description="base64内容",
     *      in="formData",
     *      name="base64",
     *      required=true,
     *      type="file"
     *   ),
     *   @SWG\Parameter(
     *     name="project_id",
     *     in="query",
     *     description="项目id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(response=200, description="上传成功", @SWG\Schema(ref="#/definitions/Nurse"))
     * )
     */
    function base64imgupload(Request $request){

        $base64 = $request->input('base64');
        $uid = $request->input('uid');
        $project_id = $request->input('project_id');
        $ary = $this->base64imgsave($base64,$uid,$project_id);
        return $this->apiReturn($ary);
    }

    //base64上传的图片储存到服务器本地
    protected function base64imgsave($img,$uid,$project_id){

        $ymd = date("Y-m-d"); //图片路径地址
        $basedir = 'hlylAssets/uploads/'.$ymd.'';
        $fullpath = $basedir;
        if(!is_dir($fullpath)){
            mkdir($fullpath,0755,true);
        }
        $types = empty($types)? array('jpg', 'gif', 'png', 'jpeg'):$types;
        $img = str_replace(array('_','-'), array('/','+'), $img);
        $b64img = substr($img, 0,100);
        if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $b64img, $matches)){
            $type = $matches[2];
            if(!in_array($type, $types)){
                return array('msg'=>'图片格式不正确，只支持 jpg、gif、png、jpeg');
            }
            $img = str_replace($matches[1], '', $img);
            $img = base64_decode($img);
            $photo = '/'.md5(date('YmdHis').rand(1000, 9999)).'.'.$type;
            file_put_contents($fullpath.$photo, $img);

            $url = env('APP_URL').'/'.$basedir.$photo;
            $tizhengReportModel = new TizhengReport();
            $id = $tizhengReportModel->insertGetId(['path'=>$url,'uid'=>$uid,'project_id'=>$project_id,'created_at'=>time(),'date'=>strtotime(date('Y-m-d',time()))]);
            if ($id){
                return ['code'=>200,'data'=>$id];
            }else{
                return ['msg'=>'图片上传失败'];
            }
        }
        return ['msg' => '请选择要上传的图片'];
    }

    public function uploadImg(Request $request){
        $ids = $request->input('ids');
        $health_profile_id = $request->input('health_profile_id');
        $tizhengReportModel = new TizhengReport();
        $tizhengReportModel->whereIn('id',explode(',',$ids))->update(['health_profile_id'=>$health_profile_id,'updated_at'=>time()]);
        $tizhengReportModel->where('health_profile_id',0)->delete();
        return $this->apiReturn(['code'=>200]);
    }

    public function getReoprtList(Request $request){
        $health_profile_id = $request->input('health_profile_id');

        $project_id = $request->input('project_id');
        $tizhengReportModel = new TizhengReport();
        $tizhengReportList = $tizhengReportModel->with(['report'=>function($query) use ($health_profile_id,$project_id){
            $query->where('health_profile_id',$health_profile_id)->where('project_id',$project_id);
        }])->where('health_profile_id',$health_profile_id)->where('project_id',$project_id)->orderBy('created_at')->groupBy(['date'])->where('is_del',1)->select('date')->get();

        foreach ($tizhengReportList as $key => $val){
            $tizhengReportList[$key]['date'] = date('Y-m-d',$val['date']);
        }
        return $this->apiReturn(['code'=>200,'data'=>$tizhengReportList]);
    }

    public function deleteImg(Request $request){
        $uid = $request->input('uid');
        $TizhengReportModel = new TizhengReport();
        if ($uid){
            $where['uid'] = $uid;
            $tizhengReportInfo = $TizhengReportModel->where($where)->first();
            $TizhengReportModel->where($where)->delete();
            return $this->apiReturn(['code'=>200,'data'=>$tizhengReportInfo['id']]);
        }
    }

    /**
     * @todo 上次base64图片
     */
    public function uploadBase64Img(Request $request){

        $img = $request->input('base64');

        $ymd = date("Y-m-d"); //图片路径地址
        $basedir = 'hlylAssets/uploads/'.$ymd.'';
        $fullpath = $basedir;
        if(!is_dir($fullpath)){
            mkdir($fullpath,0755,true);
        }
        $types = empty($types)? array('jpg', 'gif', 'png', 'jpeg'):$types;
        $img = str_replace(array('_','-'), array('/','+'), $img);
        $b64img = substr($img, 0,100);
        if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $b64img, $matches)){
            $type = $matches[2];
            if(!in_array($type, $types)){
                return array('msg'=>'图片格式不正确，只支持 jpg、gif、png、jpeg');
            }

            $img = str_replace($matches[1], '', $img);
            $img = base64_decode($img);
            $photo = '/'.md5(date('YmdHis').rand(1000, 9999)).'.'.$type;
            file_put_contents($fullpath.$photo, $img);
            $url = env('APP_URL').'/'.$basedir.$photo;

            return $this->apiReturn(['code'=>200,'data'=>$url]);
        }
        return ['msg' => '请选择要上传的图片'];
    }

}