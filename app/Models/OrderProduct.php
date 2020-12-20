<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="OrderProduct"))
 */
class OrderProduct extends Model
{

    protected $table = 'pool_order_product';
    protected $primarykey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'number',
        'asset',
        'price',
        'discount',
        'fee',
        'days',
        'imgurl',
        'imgurl2',
        'desc',
        'begintime',
        'endtime',
        'tag',
        'product_count',
        'total_price'
    ];

}