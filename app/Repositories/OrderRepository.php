<?php

namespace App\Repositories;

use App\Repositories\Traits\BaseRepository;
use App\Models\Order;

class OrderRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(Order $order)
    {
        $this->model = $order;
    }

    /**
     * 创建
     *
     * @param array $input
     * @return \App\Models\Order
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
     * @return \App\Models\Order
     */
    public function update($id, $input)
    {
        $model = $this->model->find($id);
        return $this->savePost($model, $input);
    }

    /**
     * 保存
     *
     * @param Order $order
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
     * 获取订单列表数据
     * @param $userId
     */
    public function getOrderLists($userId,$pageRows)
    {
        $result = $this->model->with(['orderProduct'])
            ->Userid($userId)
            ->latest()
            ->paginate($pageRows);
        return $result;
    }

    /*
     * 获取订单详情
     */
    public function getOrderDetail($order_id)
    {
        return $this->model->with(['orderProduct'])->find($order_id);
    }
}
