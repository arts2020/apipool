<?php
/**
 * 产品
 * @author: alan
 * @Date: 17/5/2
 */
namespace App\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Product"))
 */
class Product extends Base
{

    protected $table = 'product';

    protected $primarykey = 'id';

    public $mp_is_hot = [
        1 => '是',
        0 => '否',
    ];

    public $mp_is_show = [
        1 => '是',
        2 => '否'
    ];

    public $mp_medicals = [
        1 => '高血压',
        2 => '高血糖',
        3 => '血蛋白偏高',
        4 => '贫血',
        5 => '营养不良',
        6 => '恶性肿瘤',
        7 => '无',
    ];

    public $mp_is_follow = [
        1 => '是',
        2 => '否'
    ];

    public $mp_from_nurse_page = [
        1 => '是',
        2 => '否',
    ];

    public function category()
    {
        return $this->hasOne('\App\Models\ProductCategory', 'id', 'category_id');
    }

    public function items()
    {
        return $this->hasMany('\App\Models\ProductItems', 'id');
    }

    public function payment()
    {
        return $this->hasOne('\App\Models\ProductPayment', 'product_id', 'id');
    }

    public function giveService()
    {
        return $this->hasMany('\App\Models\GiveService', 'product_id', 'id');
    }

    public function subPrice()
    {
        return $this->hasMany('\App\Models\ProductSubPrice', 'product_id', 'id');
    }

    /**
     * 获取治疗费关联数据
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|
     */
    public function treatment()
    {
        return $this->belongsToMany(ProductTreatmentFee::class, 'product_treatment_mid', 'product_id', 'treatment_id');
    }
}