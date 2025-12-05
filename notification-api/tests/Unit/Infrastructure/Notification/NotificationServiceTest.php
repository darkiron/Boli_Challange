<?php

namespace NotificationApi\Tests\Unit\Infrastructure\Notification;

use NotificationApi\Infrastructure\Notification\NotificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotificationServiceTest extends TestCase
{
    /** @var LoggerInterface&MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testSendLogsAndReturnsTrue(): void
    {
        $service = new NotificationService($this->logger);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('Notification send'),
                $this->callback(function ($context) {
                    return is_array($context)
                        && isset($context['userId'], $context['type'], $context['payload'], $context['dryRun'])
                        && $context['userId'] === 'user-1'
                        && $context['type'] === 'alert'
                        && $context['dryRun'] === true;
                })
            );

        $ok = $service->send('user-1', 'alert', array('msg' => 'hello'), true);
        $this->assertTrue($ok);
    }
}
