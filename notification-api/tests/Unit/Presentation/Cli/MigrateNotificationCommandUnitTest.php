<?php

namespace NotificationApi\Tests\Unit\Presentation\Cli;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\DocumentManager;
use NotificationApi\Infrastructure\Persistence\Doctrine\Document\Notification;
use NotificationApi\Infrastructure\Persistence\Doctrine\Repository\NotificationRepository;
use NotificationApi\Presentation\Cli\MigrateNotificationCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateNotificationCommandUnitTest extends TestCase
{
    public function testExecuteMigration()
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $logger = $this->createMock(LoggerInterface::class);
        $dm = $this->createMock(DocumentManager::class);
        // Mock the specific repository class
        $repo = $this->createMock(NotificationRepository::class);

        $registry->expects($this->any())
            ->method('getManager')
            ->willReturn($dm);

        $dm->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        // Mock new methods
        $repo->expects($this->any())
            ->method('countAll')
            ->willReturn(1);

        $notification = new Notification('u1', 'alert', 'T1', 'B1', 'diabetes');
        $repo->expects($this->any())
            ->method('iterateAll')
            ->willReturn(new \ArrayIterator([$notification]));

        $dm->expects($this->atLeastOnce())->method('flush');
        $dm->expects($this->atLeastOnce())->method('clear');

        $command = new MigrateNotificationCommand($registry, $logger);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString('Processing Manager: default', $tester->getDisplay());
        $this->assertEquals(2, $notification->getData()['version']);
    }

    public function testExecuteRollback()
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $logger = $this->createMock(LoggerInterface::class);
        $dm = $this->createMock(DocumentManager::class);
        $repo = $this->createMock(NotificationRepository::class);

        $registry->expects($this->any())
            ->method('getManager')
            ->willReturn($dm);

        $dm->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $repo->expects($this->any())
            ->method('countAll')
            ->willReturn(1);

        $notification = new Notification('u1', 'alert', 'T1', 'B1', 'diabetes');
        $notification->setData(['version' => 2]);

        $repo->expects($this->any())
            ->method('iterateAll')
            ->willReturn(new \ArrayIterator([$notification]));

        $command = new MigrateNotificationCommand($registry, $logger);

        $tester = new CommandTester($command);
        $tester->execute(['--rollback' => true]);

        $this->assertStringContainsString('Starting Notification Rollback', $tester->getDisplay());
        $this->assertArrayNotHasKey('version', $notification->getData());
    }
}
