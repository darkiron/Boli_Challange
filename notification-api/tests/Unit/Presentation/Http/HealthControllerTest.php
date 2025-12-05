<?php

namespace NotificationApi\Tests\Unit\Presentation\Http;

use NotificationApi\Presentation\Http\HealthController;
use PHPUnit\Framework\TestCase;

class HealthControllerTest extends TestCase
{
    public function testIndexReturnsExpectedJson(): void
    {
        $controller = new HealthController('1.2.3');
        $response = $controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('ok', $data['status']);
        $this->assertSame('1.2.3', $data['version']);
        $this->assertArrayHasKey('timestamp', $data);

        $dt = \DateTimeImmutable::createFromFormat(DATE_ATOM, $data['timestamp']);
        $this->assertNotFalse($dt, 'Timestamp doit être au format ISO 8601 (DATE_ATOM)');
    }
}
