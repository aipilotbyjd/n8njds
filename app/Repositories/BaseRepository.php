<?php

namespace App\Repositories;

use App\Shared\Interfaces\CriteriaInterface;
use App\Shared\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function find(mixed $id)
    {
        return $this->model->find($id);
    }

    public function findAll()
    {
        return $this->model->all();
    }

    public function findByCriteria(CriteriaInterface $criteria)
    {
        return $criteria->apply($this->model->newQuery())->get();
    }

    public function paginate(int $perPage = 15, array $columns = ['*'])
    {
        return $this->model->paginate($perPage, $columns);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(mixed $id, array $data)
    {
        $model = $this->model->find($id);
        if ($model) {
            $model->update($data);

            return $model;
        }

        return null;
    }

    public function delete(mixed $id)
    {
        return $this->model->destroy($id);
    }
}
