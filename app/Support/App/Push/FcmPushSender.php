<?php

namespace App\Support\App\Push;

use App\Models\UsuarioAppDispositivo;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class FcmPushSender implements PushSender
{
    const OAUTH_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function provider()
    {
        return 'fcm';
    }

    public function isConfigured()
    {
        return (bool) config('services.fcm.enabled', false)
            && ! empty(config('services.fcm.project_id'))
            && $this->loadCredentials() !== null;
    }

    public function send(UsuarioAppDispositivo $dispositivo, array $message)
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'invalidToken' => false,
                'providerMessageId' => null,
                'errorCode' => 'provider_not_configured',
                'errorMessage' => 'FCM no configurado.',
            ];
        }

        $payload = [
            'message' => array_merge($message, [
                'token' => $dispositivo->push_token,
            ]),
        ];

        $response = $this->request(
            'POST',
            $this->messageUrl(),
            json_encode($payload),
            [
                'Authorization: Bearer '.$this->accessToken(),
                'Content-Type: application/json',
            ]
        );

        if ($response['status'] >= 200 && $response['status'] < 300) {
            return [
                'success' => true,
                'invalidToken' => false,
                'providerMessageId' => $response['json']['name'] ?? null,
                'errorCode' => null,
                'errorMessage' => null,
            ];
        }

        $errorCode = $response['json']['error']['status'] ?? 'FCM_ERROR';
        $errorMessage = $response['json']['error']['message'] ?? 'Error enviando push.';

        return [
            'success' => false,
            'invalidToken' => $this->isInvalidTokenError($errorCode, $errorMessage),
            'providerMessageId' => null,
            'errorCode' => $errorCode,
            'errorMessage' => $errorMessage,
        ];
    }

    protected function messageUrl()
    {
        return str_replace(
            '{project}',
            config('services.fcm.project_id'),
            config('services.fcm.send_url', 'https://fcm.googleapis.com/v1/projects/{project}/messages:send')
        );
    }

    protected function accessToken()
    {
        $cacheKey = 'services.fcm.access_token.'.sha1((string) config('services.fcm.project_id'));
        $cachedToken = Cache::get($cacheKey);

        if ($cachedToken) {
            return $cachedToken;
        }

        $credentials = $this->loadCredentials();

        if (! $credentials) {
            throw new RuntimeException('No se encontraron credenciales FCM.');
        }

        $response = $this->request(
            'POST',
            config('services.fcm.oauth_token_url', 'https://oauth2.googleapis.com/token'),
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->buildSignedJwt($credentials),
            ]),
            [
                'Content-Type: application/x-www-form-urlencoded',
            ]
        );

        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new RuntimeException($response['json']['error_description'] ?? 'No se pudo obtener access token FCM.');
        }

        $accessToken = $response['json']['access_token'] ?? null;

        if (! $accessToken) {
            throw new RuntimeException('Respuesta OAuth FCM invalida.');
        }

        $expiresIn = max(60, ((int) ($response['json']['expires_in'] ?? 3600)) - 60);
        Cache::put($cacheKey, $accessToken, now()->addSeconds($expiresIn));

        return $accessToken;
    }

    protected function buildSignedJwt(array $credentials)
    {
        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::OAUTH_SCOPE,
            'aud' => config('services.fcm.oauth_token_url', 'https://oauth2.googleapis.com/token'),
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = $header.'.'.$claims;
        $privateKey = str_replace("\\n", "\n", $credentials['private_key']);
        $signature = '';

        if (! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar el JWT FCM.');
        }

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    protected function loadCredentials()
    {
        $inlineJson = config('services.fcm.service_account_json');

        if ($inlineJson) {
            $decoded = json_decode($inlineJson, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $path = config('services.fcm.service_account_json_path');

        if (! $path) {
            return null;
        }

        $candidatePaths = [
            $path,
            base_path($path),
        ];

        foreach ($candidatePaths as $candidatePath) {
            if (is_string($candidatePath) && is_file($candidatePath)) {
                $decoded = json_decode(file_get_contents($candidatePath), true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    protected function request($method, $url, $body, array $headers)
    {
        if (! function_exists('curl_init')) {
            throw new RuntimeException('La extension cURL es requerida para FCM.');
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
        ]);

        $rawBody = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($rawBody === false) {
            throw new RuntimeException($curlError ?: 'Error de red enviando push FCM.');
        }

        return [
            'status' => $status,
            'body' => $rawBody,
            'json' => json_decode($rawBody, true) ?: [],
        ];
    }

    protected function isInvalidTokenError($errorCode, $errorMessage)
    {
        $normalizedMessage = strtoupper((string) $errorMessage);

        return in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)
            || strpos($normalizedMessage, 'UNREGISTERED') !== false
            || strpos($normalizedMessage, 'REGISTRATION TOKEN IS NOT A VALID FCM REGISTRATION TOKEN') !== false;
    }

    protected function base64UrlEncode($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
