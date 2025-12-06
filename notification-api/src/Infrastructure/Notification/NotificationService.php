<?php

namespace NotificationApi\Infrastructure\Notification;

use NotificationApi\Domain\Notification\NotificationServiceInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotificationService implements NotificationServiceInterface
{
    private HttpClientInterface $httpClient;
    private CacheItemPoolInterface $cache;
    private LoggerInterface $logger;
    private string $fcmApiKey;
    private int $rateLimit;

    public function __construct(
        HttpClientInterface $httpClient,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger,
        string $fcmApiKey,
        int $rateLimit,
    ) {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->fcmApiKey = $fcmApiKey;
        $this->rateLimit = $rateLimit;
    }

    public function send(string $userId, string $type, array $payload = [], bool $dryRun = false): bool
    {
        if (!$this->checkRateLimit($userId)) {
            $this->logger->warning('Rate limit exceeded', ['userId' => $userId]);

            return false;
        }

        if ($dryRun) {
            $this->logger->info('Dry-run notification', ['userId' => $userId, 'type' => $type]);

            return true;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://fcm.googleapis.com/fcm/send', [
                'headers' => [
                    'Authorization' => 'key='.$this->fcmApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'to' => '/topics/user-'.$userId,
                    'data' => array_merge(['type' => $type], $payload),
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('Notification sent to FCM', ['userId' => $userId, 'status' => $statusCode]);

                return true;
            }

            $this->logger->error('FCM failed', ['status' => $statusCode, 'content' => $response->getContent(false)]);

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('FCM exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function checkRateLimit(string $userId): bool
    {
        $key = 'ratelimit_'.$userId;
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            $item->set(1);
            $item->expiresAfter(60);
            $this->cache->save($item);

            return true;
        }

        $count = $item->get();
        if ($count >= $this->rateLimit) {
            return false;
        }

        $item->set($count + 1);
        $this->cache->save($item);

        return true;
    }
}
