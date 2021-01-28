<?php

namespace App\Repositories;

use App\Models\UserProfitRecord;
use App\Repositories\Traits\BaseRepository;

class UserProfitRecordRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(UserProfitRecord $model)
    {
        $this->model = $model;
    }

    /**
     * 获取收益列表
     * @param $asset
     */
    public function getProfitList($user_id,$asset)
    {
        return $this->model->Asset($asset)->Userid($user_id)->get();
    }

}
