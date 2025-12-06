<?php

namespace NotificationApi\Tests\Functional\Presentation\Cli;

use Doctrine\ODM\MongoDB\DocumentManager;
use NotificationApi\Infrastructure\Persistence\Doctrine\Document\Notification;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateNotificationCommandTest extends KernelTestCase
{
    private ?DocumentManager $dmDefault;
    private ?DocumentManager $dmWellness;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->dmDefault = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $this->dmWellness = self::getContainer()->get('doctrine_mongodb.odm.wellness_document_manager');

        $this->dmDefault->getDocumentCollection(Notification::class)->deleteMany([]);
        $this->dmWellness->getDocumentCollection(Notification::class)->deleteMany([]);
    }
    
    protected function tearDown(): void
    {
        if ($this->dmDefault) {
            $this->dmDefault->getDocumentCollection(Notification::class)->deleteMany([]);
        }
        if ($this->dmWellness) {
            $this->dmWellness->getDocumentCollection(Notification::class)->deleteMany([]);
        }
        parent::tearDown();
    }

    public function testExecute(): void
    {
        // Create dummy data in default (diabetes)
        $notif1 = new Notification('u1', 'alert', 'T1', 'B1', 'diabetes');
        $this->dmDefault->persist($notif1);
        $this->dmDefault->flush();
        
        // Create dummy data in wellness
        $notif2 = new Notification('u2', 'info', 'T2', 'B2', 'wellness');
        $this->dmWellness->persist($notif2);
        $this->dmWellness->flush();

        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:notification:migrate');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        
        $this->assertStringContainsString('Processing Manager: default', $output);
        $this->assertStringContainsString('Processing Manager: wellness', $output);
        $this->assertStringContainsString('Found 1 notifications', $output);

        // Verify data updated
        $this->dmDefault->clear();
        $updated1 = $this->dmDefault->getRepository(Notification::class)->findOneBy(['userId' => 'u1']);
        $this->assertArrayHasKey('version', $updated1->getData());
        $this->assertSame(2, $updated1->getData()['version']);
        
        $this->dmWellness->clear();
        $updated2 = $this->dmWellness->getRepository(Notification::class)->findOneBy(['userId' => 'u2']);
        $this->assertArrayHasKey('version', $updated2->getData());
        $this->assertSame(2, $updated2->getData()['version']);
    }
}
