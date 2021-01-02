<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use Traits\UserTrait;

    protected $table = 'pool_admin';

    protected $primarykey = 'id';

}