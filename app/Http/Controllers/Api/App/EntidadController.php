<?php

namespace App\Http\Controllers\Api\App;

use App\Repositories\Identity\RealIdentityRepository;

class EntidadController extends ApiController
{
    protected RealIdentityRepository $realIdentityRepository;

    public function __construct(RealIdentityRepository $realIdentityRepository)
    {
        $this->realIdentityRepository = $realIdentityRepository;
    }

    public function index()
    {
        return $this->ok($this->realIdentityRepository->activeEmpresas());
    }
}
