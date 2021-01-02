<?php
namespace App\Repositories;

use App\Models\Config;
use App\Repositories\Traits\BaseRepository;

class ConfigRepository
{
    use BaseRepository;

    protected $model;

    public function __construct(Config $model)
    {
        $this->model = $model;
    }


    public function getConfigByKey($config_key = '')
    {
        return $this->model
            ->when(($config_key), function ($query) use ($config_key) {
                return $query->Key($config_key);
            })->get();
    }
}
