<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="AssetPrice"))
 */
class AssetState extends Model
{

    protected $table = 'pool_assetstate';

    protected $primarykey = 'id';

}