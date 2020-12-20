<?php

namespace App\Repositories;

use App\Models\OrderProduct;
use App\Repositories\Traits\BaseRepository;

class OrderProductRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(OrderProduct $model)
    {
        $this->model = $model;
    }

    /**
     * 创建
     *
     * @param array $input
     * @return \App\Models\OrderProduct
     */
    public function store($input)
    {
        return $this->savePost(new $this->model, $input);
    }

    /**
     * 保存
     *
     * @param OrderProduct $order
     * @param  $input
     * @return
     */
    public function savePost($model, $input)
    {
        $model->fill($input);
        $model->save();
        return $model;
    }
}
