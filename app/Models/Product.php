<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Product"))
 */
class Product extends Model
{
    const STATE_PENDING    = 0;
    const STATE_PROCESSING = 1;
    const STATE_STOP       = 2;
    const STATE_REMOVE     = 3;

    protected $table = 'pool_product_info';

    protected $primarykey = 'id';
    public $timestamps = true;
    protected $appends = ['status_text','begin_at','price_cny'];

    public static $stateMap = [
        self::STATE_PENDING    => '待上架',
        self::STATE_PROCESSING => '售卖中',
        self::STATE_STOP       => '停售',
        self::STATE_REMOVE     => '已下架',
    ];

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
        return $query->where('state', 1);
    }

    public function getStatusTextAttribute()
    {
        $status_text = '未开始';
        if ($this->attributes['begintime'] < now())
            $status_text = '进行中';

        return $status_text;

//        return self::$stateMap[$this->attributes['state']];
    }

    public function getPriceCnyAttribute()
    {
        return turnCny($this->attributes['price'] );
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