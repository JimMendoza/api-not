<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Requests\Api\App\UpdateNotificacionConfiguracionRequest;
use App\Repositories\Notifications\RealNotificationSettingsRepository;
use App\Services\Auth\AuthenticatedAppUser;
use Illuminate\Http\Request;

class NotificacionConfiguracionController extends ApiController
{
    protected RealNotificationSettingsRepository $realSettings;

    public function __construct(RealNotificationSettingsRepository $realSettings)
    {
        $this->realSettings = $realSettings;
    }

    public function show(Request $request)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        return $this->ok($this->realSettings->settingsForUser($usuario));
    }

    public function update(UpdateNotificacionConfiguracionRequest $request)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        return $this->ok($this->realSettings->updateForUser($usuario, $request->validated()));
    }
}
