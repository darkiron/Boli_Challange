<?php

namespace NotificationApi\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use NotificationApi\Infrastructure\Persistence\Doctrine\Document\Notification;

class NotificationRepository extends ServiceDocumentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findUnreadByUser(string $userId, string $serviceName, int $limit = 20): array
    {
        return $this->createQueryBuilder()
            ->field('userId')->equals($userId)
            ->field('serviceName')->equals($serviceName)
            ->field('readAt')->equals(null)
            ->sort('createdAt', 'desc')
            ->limit($limit)
            ->select(['id', 'type', 'title', 'body', 'createdAt', 'status'])
            ->getQuery()
            ->execute()
            ->toArray();
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

        return $result['count'] ?? 0;
    }

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
                'successRate' => 0,
                'avgProcessingTime' => 0,
            ];
        }

        $total = $result['total'];
        $sent = $result['sent'];
        $successRate = $total > 0 ? ($sent / $total) * 100 : 0;

        return [
            'total' => $total,
            'byType' => [
                'alert' => $result['alertCount'],
                'reminder' => $result['reminderCount'],
                'info' => $result['infoCount'],
            ],
            'byStatus' => [
                'pending' => $result['pending'],
                'sent' => $result['sent'],
                'failed' => $result['failed'],
            ],
            'successRate' => round($successRate, 2),
            'avgProcessingTime' => $result['avgProcessingTime'],
        ];
    }

    public function findFailedNotificationsOlderThan(int $hours): array
    {
        $date = new \DateTime();
        $date->modify("-{$hours} hours");

        return $this->createQueryBuilder()
            ->field('status')->equals('failed')
            ->field('createdAt')->lte($date)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    public function countAll(): int
    {
        return $this->createQueryBuilder()->count()->getQuery()->execute();
    }

    public function iterateAll(): \Iterator
    {
        return $this->createQueryBuilder()->getQuery()->getIterator();
    }
}
