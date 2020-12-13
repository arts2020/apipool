<?php
namespace App\Repositories;

use App\Models\User;
use App\Repositories\Traits\BaseRepository;
use App\Repositories\Traits\RedisOperationTrait;

class UserRepository
{
    use BaseRepository,RedisOperationTrait;

    public function __construct(User $model)
    {
        $this->model = $model;
    }


    /**
     * 创建
     *
     * @param array $input
     * @return \App\Models\User
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
     * @return \App\Models\User
     */
    public function update($id, $input)
    {
        $model = $this->model->find($id);

        return $this->savePost($model, $input);
    }

    /**
     * 保存
     *
     * @param SysSmsLog $model
     * @param array $input
     * @return \App\Models\User
     */
    public function savePost($model, $input)
    {
        $model->fill($input);
        $model->save();

        return $model;
    }

}