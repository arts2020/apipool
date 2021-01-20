<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="ProductType"))
 */
class ProductType extends Model
{

    protected $table = 'pool_product_type';
    public $timestamps = false;

    protected $fillable = [
        'asset',
        'alias',
        'unit',
        'exchange',
        'remark'
    ];

    /**
     * 获取分类的收益
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function profit()
    {
        return $this->hasMany(UserProfit::class, 'asset', 'asset');
    }

    /**
     * 获取分类的分币记录
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function share()
    {
        return $this->hasOne(ShareCoinUser::class, 'asset', 'asset');
    }

    /**
     * 获取分类的交易记录
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function trade()
    {
        return $this->hasMany(UserTrade::class, 'asset', 'asset');
    }
}
