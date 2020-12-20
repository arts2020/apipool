<?php
namespace App\Repositories;

use App\Models\Notify;
use App\Repositories\Traits\BaseRepository;

class NotifyRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(Notify $model)
    {
        $this->model = $model;
    }

    /**
     * 获取通知列表
     * @param $asset
     */
    public function getNoticeList()
    {
        return $this->model->ExpireAt()->get();
    }
}
