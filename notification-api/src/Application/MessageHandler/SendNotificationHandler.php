<?php

namespace NotificationApi\Application\MessageHandler;

use NotificationApi\Application\Message\SendNotificationMessage;
use NotificationApi\Domain\Notification\NotificationServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'notifications')]
class SendNotificationHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(SendNotificationMessage $message): void
    {
        $this->logger->info("Handling SendNotificationMessage for user {$message->userId}");
        
        $payload = array_merge(
            ['title' => $message->title, 'body' => $message->body, 'serviceName' => $message->serviceName],
            $message->data
        );

        $success = $this->notificationService->send($message->userId, $message->type, $payload);

        if (!$success) {
            $this->logger->warning("Failed to send notification to {$message->userId}. Retrying...");
            throw new \Exception("Failed to send notification to {$message->userId}");
        }
        
        $this->logger->info("Notification sent successfully to {$message->userId}");
    }
}
