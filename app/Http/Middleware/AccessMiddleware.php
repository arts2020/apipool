<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 17/8/21
 * Time: 下午6:14
 */
namespace App\Http\Middleware;

use App\Models\Base;
use Closure;

class AccessMiddleware
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $request->input('token');
//        file_put_contents('token.log', print_r([date('Y-m-d H:i:s'), __LINE__, __METHOD__, $token], true),FILE_APPEND);
        if($token){
            $baseModel = new Base();
            $sessionData = $baseModel->get($token);

            if(intval($sessionData['userid']) == 0){
                return response()->json(['code'=>9,'msg'=>'token错误']);
            }
        }else{
            return response()->json(['code'=>100,'msg'=>'缺少token']);
        }
            
        return $next($request);
    }

}