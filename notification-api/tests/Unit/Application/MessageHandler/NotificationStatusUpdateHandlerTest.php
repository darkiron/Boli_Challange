<?php

namespace NotificationApi\Tests\Unit\Application\MessageHandler;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use NotificationApi\Application\Message\NotificationStatusUpdateMessage;
use NotificationApi\Application\MessageHandler\NotificationStatusUpdateHandler;
use NotificationApi\Infrastructure\Persistence\Doctrine\Document\Notification;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotificationStatusUpdateHandlerTest extends TestCase
{
    public function testHandleFound()
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $dm = $this->createMock(DocumentManager::class);
        $repo = $this->createMock(DocumentRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $registry->expects($this->once())->method('getManagers')->willReturn(['default' => $dm]);
        $dm->expects($this->once())->method('getRepository')->willReturn($repo);

        $notification = new Notification('u1', 't', 'title', 'body', 'svc');
        $repo->expects($this->once())->method('find')->with('123')->willReturn($notification);

        $dm->expects($this->once())->method('flush');

        $handler = new NotificationStatusUpdateHandler($registry, $logger);
        $handler(new NotificationStatusUpdateMessage('123', 'sent'));

        $this->assertEquals('sent', $notification->getStatus());
    }

    public function testHandleNotFound()
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $dm = $this->createMock(DocumentManager::class);
        $repo = $this->createMock(DocumentRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $registry->expects($this->once())->method('getManagers')->willReturn(['default' => $dm]);
        $dm->expects($this->once())->method('getRepository')->willReturn($repo);

        $repo->expects($this->once())->method('find')->willReturn(null);

        $dm->expects($this->never())->method('flush');
        $logger->expects($this->once())->method('warning');

        $handler = new NotificationStatusUpdateHandler($registry, $logger);
        $handler(new NotificationStatusUpdateMessage('123', 'sent'));
    }
}
