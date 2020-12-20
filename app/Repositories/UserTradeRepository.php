<?php
namespace App\Repositories;

use App\Models\UserTrade;
use App\Repositories\Traits\BaseRepository;

class UserTradeRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(UserTrade $model)
    {
        $this->model = $model;
    }

    /**
     * 创建
     *
     * @param array $input
     * @return \App\Models\UserTrade
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
     * @return \App\Models\UserTrade
     */
    public function update($id, $input)
    {
        $model = $this->model->find($id);
        return $this->savePost($model, $input);
    }

    /**
     * 保存
     *
     * @param UserTrade $order
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
     * 获取收益列表
     * @param $asset
     */
    public function getTradeList($user_id,$asset)
    {
        return $this->model->Asset($asset)->Userid($user_id)->State(1)->get();
    }
}
