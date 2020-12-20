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
}
