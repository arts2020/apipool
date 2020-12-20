<?php
namespace App\Repositories;

use App\Models\AssetPrice;
use App\Repositories\Traits\BaseRepository;

class AssetPriceRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(AssetPrice $model)
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
