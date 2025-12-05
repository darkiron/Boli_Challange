<?php

namespace NotificationApi\Domain\Notification;

interface NotificationServiceInterface
{
    /**
     * Envoie une notification (implémentation réelle à venir: FCM, rate limiting, logs).
     * Retourne true en cas de succès, false sinon.
     */
    public function send($userId, $type, array $payload = array(), $dryRun = false);
}
