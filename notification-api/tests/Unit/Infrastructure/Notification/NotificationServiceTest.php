<?php

namespace NotificationApi\Tests\Unit\Infrastructure\Notification;

use NotificationApi\Infrastructure\Notification\NotificationService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class NotificationServiceTest extends TestCase
{
    private $httpClient;
    private $cache;
    private $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testSendDryRun(): void
    {
        $this->setupCacheMock('user-1', 0, true);

        $service = new NotificationService($this->httpClient, $this->cache, $this->logger, 'key', 10);

        $this->logger->expects($this->once())->method('info')->with('Dry-run notification', $this->anything());
        $this->httpClient->expects($this->never())->method('request');

        $result = $service->send('user-1', 'alert', [], true);
        $this->assertTrue($result);
    }

    public function testSendSuccess(): void
    {
        $this->setupCacheMock('user-1', 0, true);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $service = new NotificationService($this->httpClient, $this->cache, $this->logger, 'key', 10);
        $this->assertTrue($service->send('user-1', 'alert'));
    }

    public function testRateLimitExceeded(): void
    {
        // Limit is 10. If count is 10, it should fail.
        $this->setupCacheMock('user-1', 10, true);

        $service = new NotificationService($this->httpClient, $this->cache, $this->logger, 'key', 10);

        $this->logger->expects($this->once())->method('warning')->with('Rate limit exceeded', $this->anything());
        $this->httpClient->expects($this->never())->method('request');

        $this->assertFalse($service->send('user-1', 'alert'));
    }

    public function testFcmFailure(): void
    {
        $this->setupCacheMock('user-1', 0, true);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);
        $response->method('getContent')->willReturn('Error');

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $service = new NotificationService($this->httpClient, $this->cache, $this->logger, 'key', 10);
        
        $this->logger->expects($this->once())->method('error')->with('FCM failed', $this->anything());
        $this->assertFalse($service->send('user-1', 'alert'));
    }

    private function setupCacheMock($userId, $currentCount, $isHit): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn($isHit);
        $item->method('get')->willReturn($currentCount);
        
        // Expect save only if allowed
        if ($isHit && $currentCount >= 10) {
             // No save if limit exceeded (or maybe yes if we want to update timestamp? Implementation returns early)
        } else {
             // Implementation calls save in both init and increment cases
             $this->cache->method('save')->with($item);
        }

        $this->cache->method('getItem')->with('ratelimit_' . $userId)->willReturn($item);
    }
}
