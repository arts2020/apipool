<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfitRecord extends Model
{

    protected $table = 'pool_user_profit_record';
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

}