<?php

namespace NotificationApi\Application\MessageHandler;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use NotificationApi\Application\Message\NotificationStatusUpdateMessage;
use NotificationApi\Infrastructure\Persistence\Doctrine\Document\Notification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'services')]
class NotificationStatusUpdateHandler
{
    public function __construct(
        private ManagerRegistry $registry,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(NotificationStatusUpdateMessage $message): void
    {
        $managers = $this->registry->getManagers();

        foreach ($managers as $name => $dm) {
            $repo = $dm->getRepository(Notification::class);
            $notification = $repo->find($message->notificationId);

            if ($notification) {
                $notification->setStatus($message->status);
                if ($message->sentAt) {
                    $notification->setSentAt($message->sentAt);
                }
                $dm->flush();
                $this->logger->info("Updated status for {$message->notificationId} in manager $name");

                return;
            }
        }

        $this->logger->warning("Notification {$message->notificationId} not found in any manager.");
    }
}
