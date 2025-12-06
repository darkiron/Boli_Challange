<?php

namespace NotificationApi\Tests\Unit\Application\MessageHandler;

use NotificationApi\Application\Message\SendNotificationMessage;
use NotificationApi\Application\MessageHandler\DeadLetterQueueHandler;
use NotificationApi\Domain\Notification\NotificationServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeadLetterQueueHandlerTest extends TestCase
{
    public function testHandleRetrySuccess()
    {
        $service = $this->createMock(NotificationServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $handler = new DeadLetterQueueHandler($service, $logger);
        
        $message = new SendNotificationMessage('u1', 'a', 't', 'b', [], 's');
        
        $service->expects($this->once())->method('send')->willReturn(true);
        $logger->expects($this->never())->method('critical'); // No archive
        
        $handler($message);
    }

    public function testHandleRetryFailureArchive()
    {
        $service = $this->createMock(NotificationServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $handler = new DeadLetterQueueHandler($service, $logger);
        
        $message = new SendNotificationMessage('u1', 'a', 't', 'b', [], 's');
        
        $service->expects($this->once())->method('send')->willReturn(false);
        $logger->expects($this->once())->method('critical'); // Archived
        
        $handler($message);
    }
}
