<?php

namespace App\Jobs;

use App\Repositories\Identity\RealIdentityRepository;
use App\Services\Push\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $usuarioId;
    public string $empresaCodigo;
    public array $notificationPayload;
    public array $logContext;

    public function __construct(int $usuarioId, string $empresaCodigo, array $notificationPayload, array $logContext = [])
    {
        $this->usuarioId = $usuarioId;
        $this->empresaCodigo = $empresaCodigo;
        $this->notificationPayload = $notificationPayload;
        $this->logContext = $logContext;
        $this->queue = (string) config('mobile.push_queue', 'push');
    }

    public function handle(RealIdentityRepository $identityRepository, PushNotificationService $pushNotificationService): void
    {
        Log::channel((string) config('mobile.log_channel', config('logging.default')))
            ->info('push.job_started', $this->baseContext());

        $usuario = $identityRepository->findUserByTokenContext($this->usuarioId, $this->empresaCodigo);

        if (! $usuario) {
            Log::channel((string) config('mobile.log_channel', config('logging.default')))
                ->warning('push.user_context_not_found', $this->baseContext());

            return;
        }

        $pushNotificationService->sendNowForUser($usuario, $this->notificationPayload, $this->baseContext());
    }

    protected function baseContext(): array
    {
        return array_merge([
            'usuarioId' => $this->usuarioId,
            'empresaCodigo' => $this->empresaCodigo,
            'notificationId' => $this->notificationPayload['id'] ?? null,
            'tramiteId' => $this->notificationPayload['tramiteId'] ?? null,
            'tipo' => $this->notificationPayload['tipo'] ?? null,
        ], $this->logContext);
    }
}
