<?php

namespace NotificationApi\Tests\Unit\Infrastructure\Persistence\Doctrine\Document;

use NotificationApi\Infrastructure\Persistence\Doctrine\Document\Notification;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    public function testNotificationCreationAndAccessors()
    {
        $userId = 'user123';
        $type = 'info';
        $title = 'Welcome';
        $body = 'Hello World';
        $serviceName = 'auth-service';
        $data = ['foo' => 'bar'];

        $notification = new Notification($userId, $type, $title, $body, $serviceName, $data);

        $this->assertNull($notification->getId());
        $this->assertEquals($userId, $notification->getUserId());
        $this->assertEquals($type, $notification->getType());
        $this->assertEquals($title, $notification->getTitle());
        $this->assertEquals($body, $notification->getBody());
        $this->assertEquals($serviceName, $notification->getServiceName());
        $this->assertEquals($data, $notification->getData());
        $this->assertEquals('pending', $notification->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $notification->getCreatedAt());
        $this->assertNull($notification->getSentAt());
        $this->assertNull($notification->getReadAt());

        // Test Setters
        $newData = ['foo' => 'baz'];
        $notification->setData($newData);
        $this->assertEquals($newData, $notification->getData());

        $notification->setStatus('sent');
        $this->assertEquals('sent', $notification->getStatus());

        $sentAt = new \DateTime();
        $notification->setSentAt($sentAt);
        $this->assertEquals($sentAt, $notification->getSentAt());

        $readAt = new \DateTime();
        $notification->setReadAt($readAt);
        $this->assertEquals($readAt, $notification->getReadAt());

        // Test ReadAt null
        $notification->setReadAt(null);
        $this->assertNull($notification->getReadAt());
    }
}
