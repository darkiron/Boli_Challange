<?php

namespace NotificationApi\Application\Command;

class SendNotificationCommand
{
    /** @var string */
    public $userId;
    /** @var string */
    public $type;
    /** @var array */
    public $payload;

    /** @var bool */
    public $dryRun;

    public function __construct($userId, $type, array $payload = array(), $dryRun = false)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->payload = $payload;
        $this->dryRun = $dryRun;
    }
}
