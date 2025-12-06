<?php

namespace NotificationApi\Application\MessageHandler;

use NotificationApi\Application\Message\BulkNotificationMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(fromTransport: 'notifications')]
class BulkNotificationHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(BulkNotificationMessage $message): void
    {
        $this->logger->info('Processing BulkNotificationMessage with '.count($message->notifications).' notifications');

        foreach ($message->notifications as $notification) {
            $this->messageBus->dispatch($notification);
        }

        $this->logger->info('Bulk processing dispatched.');
    }
}
