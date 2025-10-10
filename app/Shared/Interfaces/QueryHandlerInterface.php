<?php

namespace App\Shared\Interfaces;

interface QueryHandlerInterface
{
    public function handle(QueryInterface $query);
}
