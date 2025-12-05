<?php

namespace NotificationApi\Presentation\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

class HealthController
{
    private $appVersion;

    public function __construct($appVersion)
    {
        $this->appVersion = $appVersion;
    }

    public function index()
    {
        return new JsonResponse(array(
            'status' => 'ok',
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'version' => $this->appVersion,
        ));
    }
}
