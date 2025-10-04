<?php

namespace App\Shared\Interfaces;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command);
}