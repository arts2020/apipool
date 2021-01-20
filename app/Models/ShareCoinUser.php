<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="UserTrade"))
 */
class ShareCoinUser extends Model
{

    protected $table = 'pool_share_coin_user';
    protected $primarykey = 'id';

    /**
     * 限制查找某用户的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUserid($query,$user_id)
    {
        return $query->where('userid',$user_id);
    }

    /**
     * 限制查找某分类的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAsset($query,$type)
    {
        return $query->where('asset',$type);
    }

    /**
     * 限制查找当天的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTime($query)
    {
        return $query->whereBetween('created_at',[Carbon::today(),Carbon::tomorrow()]);
    }

}