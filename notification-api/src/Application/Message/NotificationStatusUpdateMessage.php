<?php

namespace NotificationApi\Application\Message;

class NotificationStatusUpdateMessage
{
    public function __construct(
        public readonly string $notificationId,
        public readonly string $status,
        public readonly ?\DateTimeInterface $sentAt = null
    ) {}
}
