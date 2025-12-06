<?php

namespace NotificationApi\Application\Message;

class SendNotificationMessage
{
    public function __construct(
        public readonly string $userId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data,
        public readonly string $serviceName,
    ) {
    }
}
