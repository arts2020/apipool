<?php
namespace App\Models\Traits;

use App\Models\Admin;

trait UserTrait {

    /**
     * 获取操作人员信息
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class,'created_by','id');
    }

}