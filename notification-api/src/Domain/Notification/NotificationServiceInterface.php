<?php

namespace NotificationApi\Domain\Notification;

interface NotificationServiceInterface
{
    /**
     * Envoie une notification (implémentation réelle à venir: FCM, rate limiting, logs).
     * Retourne true en cas de succès, false sinon.
     *
     * @param array<string, mixed> $payload
     */
    public function send(string $userId, string $type, array $payload = [], bool $dryRun = false): bool;
}
