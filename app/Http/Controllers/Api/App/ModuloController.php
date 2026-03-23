<?php

namespace App\Http\Controllers\Api\App;

class ModuloController extends ApiController
{
    public function index()
    {
        return $this->ok($this->appModules());
    }
}
