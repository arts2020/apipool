<?php
namespace App\Repositories;

use App\Models\LoginLog;
use App\Repositories\Traits\BaseRepository;

class LoginLogRepository
{
    use BaseRepository;

    public function __construct(LoginLog $model)
    {
        $this->model = $model;
    }


    /**
     * 创建
     *
     * @param array $input
     * @return \App\Models\LoginLog
     */
    public function store($input)
    {
        return $this->savePost(new $this->model, $input);
    }

    /**
     * 保存
     *
     * @param SysSmsLog $model
     * @param array $input
     * @return \App\Models\LoginLog
     */
    public function savePost($model, $input)
    {
        $model->fill($input);
        $model->save();

        return $model;
    }

}