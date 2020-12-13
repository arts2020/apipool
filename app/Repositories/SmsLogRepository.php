<?php
namespace App\Repositories;

use App\Models\SysSmsLog;
use App\Repositories\Traits\BaseRepository;

class SmsLogRepository
{
    use BaseRepository;

    public function __construct(SysSmsLog $model)
    {
        $this->model = $model;
    }


    /**
     * 创建
     *
     * @param array $input
     * @return \App\Models\SysSmsLog
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
     * @return \App\Models\SysSmsLog
     */
    public function savePost($model, $input)
    {
        $model->fill($input);
        $model->save();

        return $model;
    }

}