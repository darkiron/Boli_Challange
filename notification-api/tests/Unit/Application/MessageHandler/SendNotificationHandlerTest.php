<?php

namespace NotificationApi\Tests\Unit\Application\MessageHandler;

use NotificationApi\Application\Message\SendNotificationMessage;
use NotificationApi\Application\MessageHandler\SendNotificationHandler;
use NotificationApi\Domain\Notification\NotificationServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendNotificationHandlerTest extends TestCase
{
    public function testHandleSuccess()
    {
        $service = $this->createMock(NotificationServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $handler = new SendNotificationHandler($service, $logger);

        $message = new SendNotificationMessage('u1', 'alert', 'Title', 'Body', ['k' => 'v'], 'srv');

        $service->expects($this->once())
            ->method('send')
            ->with('u1', 'alert', ['title' => 'Title', 'body' => 'Body', 'serviceName' => 'srv', 'k' => 'v'])
            ->willReturn(true);

        $handler($message);
    }

    public function testHandleFailure()
    {
        $service = $this->createMock(NotificationServiceInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $handler = new SendNotificationHandler($service, $logger);

        $message = new SendNotificationMessage('u1', 'alert', 'Title', 'Body', [], 'srv');

        $service->expects($this->once())
            ->method('send')
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $handler($message);
    }
}
