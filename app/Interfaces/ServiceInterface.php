<?php

namespace App\Interfaces;

interface ServiceInterface
{
    /**
     * Define what the rules for the service should be.
     */
    public function rules(): array;

    /**
     * Define what the permissions for the service should be.
     */
    public function permissions(): array;
}
