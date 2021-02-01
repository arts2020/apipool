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
     * 创建
     *
     * @param array $input
     * @return \App\Models\UserAsset
     */
    public function store($input)
    {
        return $this->savePost(new $this->model, $input);
    }

    /**
     * 更新
     *
     * @param int $id
     * @param array $input
     * @return \App\Models\UserAsset
     */
    public function update($id, $input)
    {
        $model = $this->model->find($id);
        return $this->savePost($model, $input);
    }

    /**
     * 保存
     *
     * @param UserAsset $model
     * @param  $input
     * @return
     */
    public function savePost($model, $input)
    {
        $model->fill($input);
        $model->save();
        return $model;
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
     * @param $userid
     * @param $asset
     * @return mixed
     */
    public function getAssetInfo($userid, $asset = 'filecoin')
    {
        return $this->model->Userid($userid)->Asset($asset)->where('address', '<>', 'undefined')->first();
    }

    /**
     * 获取钱包地址&&收益
     * @param $userid
     * @param $asset
     * @return mixed
     */
    public function getAssetDetail($userid, $asset)
    {
        return $this->model->with(['profit' => function ($query) use ($userid) {
            $query->Userid($userid);
        }])->Userid($userid)->Asset($asset)->where('address', '<>', 'undefined')->first();
    }
}
