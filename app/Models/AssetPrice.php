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

    /**
     * 动态格式化时间戳
     */
    public function getLastUpdatedAttribute()
    {
        return \Carbon\Carbon::createFromTimestamp($this->attributes['last_updated'])->toDateString();
    }
}