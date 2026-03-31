<?php

namespace App\Modules\Notificaciones\Controllers\Api;

use App\Modules\Auth\Support\AuthenticatedAppUser;
use App\Modules\Notificaciones\Repositories\RealNotificationSettingsRepository;
use App\Modules\Notificaciones\Requests\UpdateNotificacionConfiguracionRequest;
use App\Modules\Notificaciones\Resources\NotificationSettingsResource;
use App\Support\Http\Controllers\ApiController;
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

        return $this->ok(
            (new NotificationSettingsResource($this->realSettings->settingsForUser($usuario)))->resolve($request)
        );
    }

    public function update(UpdateNotificacionConfiguracionRequest $request)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        return $this->ok(
            (new NotificationSettingsResource($this->realSettings->updateForUser($usuario, $request->validated())))->resolve($request)
        );
    }
}