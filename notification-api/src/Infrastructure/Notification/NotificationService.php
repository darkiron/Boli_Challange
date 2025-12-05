<?php

namespace NotificationApi\Infrastructure\Notification;

use NotificationApi\Domain\Notification\NotificationServiceInterface;
use Psr\Log\LoggerInterface;

class NotificationService implements NotificationServiceInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function send($userId, $type, array $payload = array(), $dryRun = false)
    {
        // Impl. minimale: log et simuler succès
        $this->logger->info('Notification send called', array(
            'userId' => $userId,
            'type' => $type,
            'payload' => $payload,
            'dryRun' => $dryRun,
        ));
        return true;
    }
}
