<?php
namespace App\Repositories;

use App\Models\AssetState;
use App\Repositories\Traits\BaseRepository;

class AssetStateRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(AssetState $model)
    {
        $this->model = $model;
    }

    /**
     * 列表数据
     */
    public function getList()
    {
        return $this->model->get();
    }
}
