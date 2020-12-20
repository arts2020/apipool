<?php
namespace App\Repositories;

use App\Models\UserAsset;
use App\Repositories\Traits\BaseRepository;

class UserAssetRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(UserAsset $model)
    {
        $this->model = $model;
    }

    /**
     * 钱包明细
     * @param $user_id
     */
    public function getAssetList($user_id)
    {
        return $this->model->Userid($user_id)->get();
    }

    /**
     * 获取钱包地址
     * @param $user_id
     * @param $asset
     * @return mixed
     */
    public function getAssetInfo($user_id,$asset)
    {
        return $this->model->Userid($user_id)->Asset($asset)->first();
    }
}
