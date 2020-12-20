<?php
namespace App\Repositories;

use App\Models\Notice;
use App\Repositories\Traits\BaseRepository;

class NoticeRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(Notice $model)
    {
        $this->model = $model;
    }

    /**
     * 获取分类下的公告列表
     * @param $asset
     */
    public function getNoticeList($asset)
    {
        return $this->model->Type($asset)->State()->get();
    }
}
