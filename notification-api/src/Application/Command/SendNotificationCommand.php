<?php

namespace NotificationApi\Application\Command;

class SendNotificationCommand
{
    public string $userId;

    public string $type;

    /** @var array<string, mixed> */
    public array $payload;

    public bool $dryRun;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(string $userId, string $type, array $payload = [], bool $dryRun = false)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->payload = $payload;
        $this->dryRun = $dryRun;
    }
}
