<?php

namespace App\Modules\Auth\Controllers\Api;

use App\Support\Http\Controllers\ApiController;

class ModuloController extends ApiController
{
    public function index()
    {
        return $this->ok($this->appModules());
    }
}