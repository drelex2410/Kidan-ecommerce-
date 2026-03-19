<?php

namespace App\Contracts;

interface ApplicationBootstrap
{
    public function initialize(): void;
}
