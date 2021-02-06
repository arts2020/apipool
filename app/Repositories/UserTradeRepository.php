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
        return $this->model
            ->State(UserTrade::STATE_SUCCESS)
            ->Asset($asset)->Userid($user_id)
            ->get();
    }


    /**
     * 获取除提币外的交易记录
     * @param $asset
     */
    public function getTradeListOther($user_id,$asset)
    {
        return $this->model
            ->Asset($asset)->Userid($user_id)->State(UserTrade::STATE_SUCCESS)
            ->NotType(UserTrade::TYPE_DRAW_COIN_PROFIT)
            ->get();
    }

    /**
     * 获取提币记录
     * @param $asset
     */
    public function getTradeListForCoin($user_id,$asset)
    {
        return $this->model
            ->Asset($asset)->Userid($user_id)->Type(UserTrade::TYPE_DRAW_COIN_PROFIT)
            ->latest('created_at')
            ->get();
    }
}
