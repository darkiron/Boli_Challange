<?php

namespace NotificationApi\Tests\Unit\Application\MessageHandler;

use NotificationApi\Application\Message\BulkNotificationMessage;
use NotificationApi\Application\Message\SendNotificationMessage;
use NotificationApi\Application\MessageHandler\BulkNotificationHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class BulkNotificationHandlerTest extends TestCase
{
    public function testHandle()
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $handler = new BulkNotificationHandler($bus, $logger);

        $msg1 = new SendNotificationMessage('u1', 'a', 't', 'b', [], 's');
        $msg2 = new SendNotificationMessage('u2', 'a', 't', 'b', [], 's');
        $bulk = new BulkNotificationMessage([$msg1, $msg2]);

        $bus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn(new Envelope($msg1));

        $handler($bulk);
    }
}
