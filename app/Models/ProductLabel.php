<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="ProductLabel"))
 */
class ProductLabel extends Model
{

    protected $table = 'pool_product_label';
    public $timestamps = false;

    protected $fillable = [
        'title'
    ];
}
