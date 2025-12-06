<?php

namespace NotificationApi\Infrastructure\Monitoring;

use Psr\Log\LoggerInterface;

class NotificationMetricsService
{
    private int $processed = 0;
    private int $failed = 0;

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function incrementProcessed(): void
    {
        ++$this->processed;
        $this->logMetrics();
    }

    public function incrementFailed(): void
    {
        ++$this->failed;
        $this->logMetrics();
    }

    private function logMetrics(): void
    {
        if (($this->processed + $this->failed) % 100 === 0) {
            $this->logger->info("Notification Metrics: Processed: {$this->processed}, Failed: {$this->failed}");
        }
    }
}
