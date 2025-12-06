<?php

namespace NotificationApi\Tests\Unit\Presentation\Cli;

use NotificationApi\Domain\Notification\NotificationServiceInterface;
use NotificationApi\Presentation\Cli\TestNotificationCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TestNotificationCommandTest extends TestCase
{
    public function testExecuteSuccess(): void
    {
        $service = $this->createMock(NotificationServiceInterface::class);
        $service->expects($this->once())
            ->method('send')
            ->with('user-1', 'alert', $this->anything(), false)
            ->willReturn(true);

        $command = new TestNotificationCommand($service);
        $commandTester = new CommandTester($command);

        $code = $commandTester->execute([
            'userId' => 'user-1',
            'type' => 'alert',
        ]);

        $this->assertSame(Command::SUCCESS, $code);
        $this->assertStringContainsString('Notification sent successfully', $commandTester->getDisplay());
    }

    public function testExecuteFailure(): void
    {
        $service = $this->createMock(NotificationServiceInterface::class);
        $service->expects($this->once())
            ->method('send')
            ->willReturn(false);

        $command = new TestNotificationCommand($service);
        $commandTester = new CommandTester($command);

        $code = $commandTester->execute([
            'userId' => 'user-1',
            'type' => 'alert',
        ]);

        $this->assertSame(Command::FAILURE, $code);
        $this->assertStringContainsString('Failed to send', $commandTester->getDisplay());
    }

    public function testExecuteDryRun(): void
    {
        $service = $this->createMock(NotificationServiceInterface::class);
        $service->expects($this->once())
            ->method('send')
            ->with('user-1', 'alert', $this->anything(), true) // dryRun=true
            ->willReturn(true);

        $command = new TestNotificationCommand($service);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'userId' => 'user-1',
            'type' => 'alert',
            '--dry-run' => true,
        ]);

        $this->assertStringContainsString('Mode: Dry Run', $commandTester->getDisplay());
    }
}
