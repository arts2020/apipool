<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Product"))
 */
class Product extends Model
{

    protected $table = 'pool_product_info';

    protected $primarykey = 'id';
    public $timestamps = true;
    protected $appends = ['status_text','begin_at'];

    /**
     * 限制查找某分类的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAsset($query,$asset)
    {
        return $query->where('asset',$asset);
    }

    /**
     * 限制查找未下架的数据
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeState($query)
    {
        return $query->where('state', '<>', 2);
    }

    public function getStatusTextAttribute()
    {
        $status_text = '未开始';
        if ($this->attributes['begintime'] < now())
            $status_text = '进行中';

        return $status_text;
    }

    /**
     * 动态格式化时间戳
     */
    public function getBeginAtAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['begintime'])->toDateString();
    }
    /**
     * 获取商品所属分类
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(ProductType::class, 'asset', 'asset');
    }

}