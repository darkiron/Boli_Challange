<?php

namespace NotificationApi\Application\MessageHandler;

use NotificationApi\Application\Message\SendNotificationMessage;
use NotificationApi\Domain\Notification\NotificationServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'failed')]
class DeadLetterQueueHandler
{
    public function __construct(
        private NotificationServiceInterface $notificationService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(SendNotificationMessage $message): void
    {
        $this->logger->warning("Processing failed message for user {$message->userId} from Dead Letter Queue.");

        $payload = array_merge(
            ['title' => $message->title, 'body' => $message->body, 'serviceName' => $message->serviceName],
            $message->data
        );

        // Attempt one last time
        $success = $this->notificationService->send($message->userId, $message->type, $payload);

        if ($success) {
            $this->logger->info("Successfully recovered message for {$message->userId} from DLQ.");
            return;
        }

        // Archive
        $this->archiveMessage($message);
    }

    private function archiveMessage(SendNotificationMessage $message): void
    {
        // In a real app, save to DB. Here, we log as critical archive.
        $this->logger->critical("ARCHIVING PERMANENTLY FAILED MESSAGE: " . json_encode($message));
    }
}
