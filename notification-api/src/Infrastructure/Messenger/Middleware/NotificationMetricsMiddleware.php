<?php

namespace NotificationApi\Infrastructure\Messenger\Middleware;

use NotificationApi\Infrastructure\Monitoring\NotificationMetricsService;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class NotificationMetricsMiddleware implements MiddlewareInterface
{
    public function __construct(private NotificationMetricsService $metricsService)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            $envelope = $stack->next()->handle($envelope, $stack);
            $this->metricsService->incrementProcessed();

            return $envelope;
        } catch (\Throwable $e) {
            $this->metricsService->incrementFailed();
            throw $e;
        }
    }
}
