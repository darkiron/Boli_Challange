<?php

namespace NotificationApi\Tests\Functional\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use NotificationApi\Infrastructure\Persistence\Doctrine\Document\Notification;
use NotificationApi\Infrastructure\Persistence\Doctrine\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotificationRepositoryTest extends KernelTestCase
{
    private ?DocumentManager $dm;
    private ?NotificationRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $this->repository = $this->dm->getRepository(Notification::class);

        // Clear collection
        $this->dm->getDocumentCollection(Notification::class)->deleteMany([]);
    }

    protected function tearDown(): void
    {
        if ($this->dm) {
            $this->dm->getDocumentCollection(Notification::class)->deleteMany([]);
            $this->dm->close();
        }
        parent::tearDown();
    }

    public function testFindUnreadByUser(): void
    {
        $notif1 = new Notification('user1', 'alert', 'Title 1', 'Body 1', 'diabetes');
        $notif2 = new Notification('user1', 'info', 'Title 2', 'Body 2', 'diabetes');
        $notif2->setReadAt(new \DateTimeImmutable());
        $notif3 = new Notification('user2', 'alert', 'Title 3', 'Body 3', 'diabetes');

        $this->dm->persist($notif1);
        $this->dm->persist($notif2);
        $this->dm->persist($notif3);
        $this->dm->flush();

        $unread = $this->repository->findUnreadByUser('user1', 'diabetes');

        $this->assertCount(1, $unread);
        $this->assertSame('Title 1', $unread[0]->getTitle());
    }

    public function testCountByStatusAndService(): void
    {
        $notif1 = new Notification('user1', 'alert', 'T1', 'B1', 'diabetes');
        $notif1->setStatus('sent');

        $notif2 = new Notification('user2', 'alert', 'T2', 'B2', 'diabetes');
        $notif2->setStatus('pending');

        $notif3 = new Notification('user3', 'alert', 'T3', 'B3', 'wellness');
        $notif3->setStatus('sent');

        $this->dm->persist($notif1);
        $this->dm->persist($notif2);
        $this->dm->persist($notif3);
        $this->dm->flush();

        $count = $this->repository->countByStatusAndService('sent', 'diabetes', new \DateTime('-1 day'), new \DateTime('+1 day'));
        $this->assertSame(1, $count);
    }

    public function testGetStatisticsByService(): void
    {
        $notif1 = new Notification('u1', 'alert', 'T1', 'B1', 'diabetes');
        $notif1->setStatus('sent');

        // Set sentAt strictly after createdAt
        // createdAt is now. sentAt = now + 1s
        $createdAt = $notif1->getCreatedAt();
        // Since createdAt is immutable and set in constructor, we can assume it's roughly now.
        // But to be safe for stats avg calculation test, let's set sentAt 1s after.
        // Or we can modify createdAt to be in past.

        $ref = new \ReflectionClass($notif1);
        $prop = $ref->getProperty('createdAt');
        $prop->setAccessible(true);
        $startTime = new \DateTimeImmutable('-10 seconds');
        $prop->setValue($notif1, $startTime);

        $notif1->setSentAt($startTime->modify('+5 seconds')); // 5s duration

        $notif2 = new Notification('u2', 'reminder', 'T2', 'B2', 'diabetes');
        $notif2->setStatus('failed');

        $notif3 = new Notification('u3', 'info', 'T3', 'B3', 'wellness');

        $this->dm->persist($notif1);
        $this->dm->persist($notif2);
        $this->dm->persist($notif3);
        $this->dm->flush();

        $stats = $this->repository->getStatisticsByService('diabetes', new \DateTime('-1 hour'), new \DateTime('+1 hour'));

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['byStatus']['sent']);
        $this->assertSame(1, $stats['byStatus']['failed']);
        $this->assertSame(1, $stats['byType']['alert']);
        $this->assertSame(1, $stats['byType']['reminder']);
        $this->assertEquals(50.0, $stats['successRate']);
        $this->assertEquals(5000, $stats['avgProcessingTime'], 'Avg processing time should be ~5000ms');
    }

    public function testFindFailedNotificationsOlderThan(): void
    {
        $oldFailed = new Notification('u1', 'alert', 'T1', 'B1', 'diabetes');
        $oldFailed->setStatus('failed');

        $ref = new \ReflectionClass($oldFailed);
        $prop = $ref->getProperty('createdAt');
        $prop->setAccessible(true);
        $prop->setValue($oldFailed, new \DateTimeImmutable('-25 hours'));

        $recentFailed = new Notification('u2', 'alert', 'T2', 'B2', 'diabetes');
        $recentFailed->setStatus('failed'); // Recent

        $this->dm->persist($oldFailed);
        $this->dm->persist($recentFailed);
        $this->dm->flush();

        $results = $this->repository->findFailedNotificationsOlderThan(24);

        $this->assertCount(1, $results);
        $this->assertSame('T1', $results[0]->getTitle());
    }
}
