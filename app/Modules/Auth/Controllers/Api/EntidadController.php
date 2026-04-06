<?php

namespace App\Modules\Auth\Controllers\Api;

use App\Modules\Auth\Repositories\RealIdentityRepository;
use App\Support\Http\Controllers\ApiController;

class EntidadController extends ApiController
{
    protected RealIdentityRepository $realIdentityRepository;

    public function __construct(RealIdentityRepository $realIdentityRepository)
    {
        $this->realIdentityRepository = $realIdentityRepository;
    }

    public function index()
    {
        return $this->ok($this->realIdentityRepository->ListEmpresas());
    }
}