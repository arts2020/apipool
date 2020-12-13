<?php

namespace App\Repositories\Traits;

trait BaseRepository
{
    /**
     * 字段值+1
     * @param string $column
     * @param int $num
     * @param int $id
     * @return number
     */
    public function increment($id, $column, $num = 1)
    {
        $model = $this->model->find($id);

        return $model->increment($column, $num);
    }

    /**
     * 字段值-1
     * @param string $column
     * @param int $num
     * @param int $id
     * @return number
     */
    public function decrement($id, $column, $num = 1)
    {
        $model = $this->model->find($id);

        return $model->decrement($column, $num);
    }

    /**
     * 删除数据
     * @param $id
     */
    public function destroy($id)
    {
        $this->getById($id)->delete();
    }

    /**
     * 获取数据明细
     * @param $id
     * @param string $with
     * @return mixed
     */
    public function getById($id, $with = '')
    {
        if ($with)
            return $this->model->with($with)->find($id);
        else
            return $this->model->find($id);
    }

    /**
     * 批量获取数据
     * @param $ids
     * @return mixed
     */
    public function getByIds($ids)
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    /**
     * 根据某些条件获取数据
     * @param $attr
     * @return mixed
     */
    public function getByAttr($attr)
    {
        return $this->model->where($attr)->first();
    }

}
