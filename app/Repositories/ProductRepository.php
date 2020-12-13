<?php
namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(Product $product)
    {
        $this->model = $product;
    }

    /**
     * 返回产品下的治疗费数据
     * @param $productId
     */
    public function getTreatmentList($productId)
    {
        return $this->model->with('treatment')->find($productId)->treatment??[];
    }
}
