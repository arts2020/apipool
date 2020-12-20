<?php
namespace App\Repositories;

use App\Models\UserProfit;
use App\Repositories\Traits\BaseRepository;

class UserProfitRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(UserProfit $model)
    {
        $this->model = $model;
    }

    /**
     * 获取收益
     * @param $asset
     */
    public function getProfit($user_id,$asset)
    {
        return $this->model->Asset($asset)->Userid($user_id)->sum('count');
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
