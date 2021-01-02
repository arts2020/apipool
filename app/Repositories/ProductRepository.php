<?php
namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Traits\BaseRepository;

class ProductRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(Product $product)
    {
        $this->model = $product;
    }

    /**
     * 获取分类下的产品列表
     * @param $asset
     */
    public function getProductList($asset,$source,$index)
    {
        return $this->model->with(['type'])->Asset($asset)->State()
            ->when(($source && $source == 'index' && $index > 0), function ($query) use ($index) {
                return $query->take($index);
            })
            ->get();
    }
}
