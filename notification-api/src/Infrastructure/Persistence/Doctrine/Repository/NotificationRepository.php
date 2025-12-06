<?php

namespace NotificationApi\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use NotificationApi\Infrastructure\Persistence\Doctrine\Document\Notification;

use Doctrine\ODM\MongoDB\Iterator\Iterator;

/**
 * @extends ServiceDocumentRepository<Notification>
 */
class NotificationRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return Notification[]
     */
    public function findUnreadByUser(string $userId, string $serviceName, int $limit = 20): array
    {
        $result = $this->createQueryBuilder()
            ->field('userId')->equals($userId)
            ->field('serviceName')->equals($serviceName)
            ->field('readAt')->equals(null)
            ->sort('createdAt', 'desc')
            ->limit($limit)
            ->select(['id', 'type', 'title', 'body', 'createdAt', 'status'])
            ->getQuery()
            ->execute();

        if ($result instanceof Iterator) {
             return $result->toArray();
        }

        return is_array($result) ? $result : [];
    }

    public function countByStatusAndService(string $status, string $serviceName, \DateTime $startDate, \DateTime $endDate): int
    {
        $builder = $this->createAggregationBuilder();
        $builder
            ->match()
                ->field('status')->equals($status)
                ->field('serviceName')->equals($serviceName)
                ->field('createdAt')->gte($startDate)
                ->field('createdAt')->lte($endDate)
            ->count('count');

        $result = $builder->getAggregation()->getIterator()->current();

        return is_array($result) ? ($result['count'] ?? 0) : 0;
    }

    /**
     * @return array{
     *     total: int,
     *     byType: array<string, int>,
     *     byStatus: array<string, int>,
     *     successRate: float,
     *     avgProcessingTime: int|float
     * }
     */
    public function getStatisticsByService(string $serviceName, \DateTime $startDate, \DateTime $endDate): array
    {
        $builder = $this->createAggregationBuilder();
        $builder
            ->match()
                ->field('serviceName')->equals($serviceName)
                ->field('createdAt')->gte($startDate)
                ->field('createdAt')->lte($endDate);

        // Calculate duration (sentAt - createdAt) only if sentAt is present
        $builder->project()
            ->includeFields(['type', 'status', 'createdAt', 'sentAt'])
            ->field('duration')->expression(
                $builder->expr()->cond(
                    $builder->expr()->gt('$sentAt', null),
                    $builder->expr()->subtract('$sentAt', '$createdAt'),
                    null
                )
            );

        $builder->group()
            ->field('id')->expression(null)
            ->field('total')->sum(1)
            ->field('pending')->sum($builder->expr()->cond($builder->expr()->eq('$status', 'pending'), 1, 0))
            ->field('sent')->sum($builder->expr()->cond($builder->expr()->eq('$status', 'sent'), 1, 0))
            ->field('failed')->sum($builder->expr()->cond($builder->expr()->eq('$status', 'failed'), 1, 0))
            ->field('alertCount')->sum($builder->expr()->cond($builder->expr()->eq('$type', 'alert'), 1, 0))
            ->field('reminderCount')->sum($builder->expr()->cond($builder->expr()->eq('$type', 'reminder'), 1, 0))
            ->field('infoCount')->sum($builder->expr()->cond($builder->expr()->eq('$type', 'info'), 1, 0))
            ->field('avgProcessingTime')->avg('$duration');

        $result = $builder->getAggregation()->getIterator()->current();

        if (!$result) {
            return [
                'total' => 0,
                'byType' => ['alert' => 0, 'reminder' => 0, 'info' => 0],
                'byStatus' => ['pending' => 0, 'sent' => 0, 'failed' => 0],
                'successRate' => 0.0,
                'avgProcessingTime' => 0,
            ];
        }

        $total = $result['total'];
        $sent = $result['sent'];
        $successRate = $total > 0 ? ($sent / $total) * 100 : 0;

        return [
            'total' => (int) $total,
            'byType' => [
                'alert' => (int) $result['alertCount'],
                'reminder' => (int) $result['reminderCount'],
                'info' => (int) $result['infoCount'],
            ],
            'byStatus' => [
                'pending' => (int) $result['pending'],
                'sent' => (int) $result['sent'],
                'failed' => (int) $result['failed'],
            ],
            'successRate' => round((float) $successRate, 2),
            'avgProcessingTime' => $result['avgProcessingTime'],
        ];
    }

    /**
     * @return Notification[]
     */
    public function findFailedNotificationsOlderThan(int $hours): array
    {
        $date = new \DateTime();
        $date->modify("-{$hours} hours");

        $result = $this->createQueryBuilder()
            ->field('status')->equals('failed')
            ->field('createdAt')->lte($date)
            ->getQuery()
            ->execute();

        if ($result instanceof Iterator) {
             return $result->toArray();
        }

        return is_array($result) ? $result : [];
    }

    public function countAll(): int
    {
        $count = $this->createQueryBuilder()->count()->getQuery()->execute();
        return is_numeric($count) ? (int) $count : 0;
    }

    public function iterateAll(): \Iterator
    {
        $iterator = $this->createQueryBuilder()->getQuery()->getIterator();
        if (!$iterator instanceof \Iterator) {
            return new \ArrayIterator([]);
        }
        return $iterator;
    }
}
