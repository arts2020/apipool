<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="AssetPrice"))
 */
class AssetPrice extends Model
{

    protected $table = 'pool_assetprice';

    protected $primarykey = 'id';

    protected $appends = ['price_cny'];

    /**
     * 返回cny字段
     */
    public function getPriceCnyAttribute()
    {
        return turnCny($this->attributes['price_usd']);
    }


    public function scopeType($query,$asset)
    {
        return $query->where('symbol', $asset);
    }

    /**
     * 动态格式化时间戳
     */
    public function getLastUpdatedAttribute()
    {
        return \Carbon\Carbon::createFromTimestamp($this->attributes['last_updated'])->toDateString();
    }
}