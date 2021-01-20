<?php

namespace App\Repositories;

use App\Models\ProductType;
use App\Repositories\Traits\BaseRepository;
use App\Models\UserTrade;

class ProductTypeRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(ProductType $model)
    {
        $this->model = $model;
    }

    /**
     * 列表
     */
    public function getList()
    {
        return $this->model->orderBy('id', 'asc')->get();
    }


    /**
     * 获取分类下用户的收益
     */
    public function getListWithProfit($userid)
    {
        return $this->model->with(['profit' => function ($query) use ($userid) {
            $query->Userid($userid);
        },'share'=> function ($query) use ($userid) {
            $query->Userid($userid)->ByTime();
        },'trade'=> function ($query) use ($userid) {
            $query->Userid($userid)->State(UserTrade::STATE_PROCESSING)->Type(UserTrade::TYPE_DRAW_COIN_PROFIT);
        }])->orderBy('id', 'asc')->get();
    }
}
