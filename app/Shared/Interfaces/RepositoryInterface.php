<?php

namespace App\Shared\Interfaces;

interface RepositoryInterface
{
    public function find(mixed $id);
    public function findAll();
    public function create(array $data);
    public function update(mixed $id, array $data);
    public function delete(mixed $id);
}