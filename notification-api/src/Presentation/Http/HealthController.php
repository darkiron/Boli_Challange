<?php

namespace NotificationApi\Presentation\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

class HealthController
{
    private string $appVersion;

    public function __construct(string $appVersion)
    {
        $this->appVersion = $appVersion;
    }

    public function index(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'version' => $this->appVersion,
        ]);
    }
}
