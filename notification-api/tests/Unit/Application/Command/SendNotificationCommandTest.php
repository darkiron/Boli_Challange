<?php

namespace NotificationApi\Tests\Unit\Application\Command;

use NotificationApi\Application\Command\SendNotificationCommand;
use PHPUnit\Framework\TestCase;

class SendNotificationCommandTest extends TestCase
{
    public function testConstructorAssignsProperties(): void
    {
        $cmd = new SendNotificationCommand('u-123', 'reminder', ['title' => 'Hi'], true);

        $this->assertSame('u-123', $cmd->userId);
        $this->assertSame('reminder', $cmd->type);
        $this->assertSame(['title' => 'Hi'], $cmd->payload);
        $this->assertTrue($cmd->dryRun);
    }

    public function testDefaultsWhenPayloadAndDryRunOmitted(): void
    {
        $cmd = new SendNotificationCommand('u-1', 'info');
        $this->assertSame([], $cmd->payload);
        $this->assertFalse($cmd->dryRun);
    }
}
