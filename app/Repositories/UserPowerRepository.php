<?php
namespace App\Repositories;

use App\Models\UserPower;
use App\Repositories\Traits\BaseRepository;

class UserPowerRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(UserPower $model)
    {
        $this->model = $model;
    }

    /**
     * 获取分类下的算力列表
     * @param $asset
     */
    public function getPowerList($user_id,$asset)
    {
        return $this->model->with(['order'])->Asset($asset)->Userid($user_id)->State(1)->get();
    }

    /**
     * 获取全部算力
     * @param $asset
     */
    public function getTotalPower($user_id,$asset)
    {
        return $this->model->Asset($asset)->Userid($user_id)->sum('power');
    }

    /**
     * 获取有效算力
     * @param $asset
     */
    public function getValidPower($user_id,$asset)
    {
        return $this->model->Asset($asset)->Userid($user_id)->State(1)->sum('power');
    }
}
