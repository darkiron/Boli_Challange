<?php

namespace NotificationApi\Application\Message;

class BulkNotificationMessage
{
    /**
     * @param SendNotificationMessage[] $notifications
     */
    public function __construct(
        public readonly array $notifications
    ) {}
}
