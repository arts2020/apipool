<?php
/**
 * 产品分类
 * @author: alan
 * @Date: 17/5/2
 */
namespace App\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="ProductCategory"))
 */
class ProductCategory extends Base {

    protected $table = 'product_category';

    protected $primarykey = 'id';


}