<?php

namespace NotificationApi\Tests\Functional;

use NotificationApi\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class KernelBootTest extends TestCase
{
    public function testKernelBootsAndHealthControllerIsCallable()
    {
        $kernel = new Kernel('test', false);
        $kernel->boot();

        $container = $kernel->getContainer();
        $this->assertInstanceOf(ContainerInterface::class, $container);

        // Vérifie que la route "/health" est bien enregistrée (charge la config de routes)
        $router = $container->get('router');
        $this->assertSame('/health', $router->generate('health'));

        // Récupère le contrôleur (public) et appelle l'action
        $controller = $container->get('NotificationApi\\Presentation\\Http\\HealthController');
        $this->assertIsObject($controller);

        $response = $controller->index();
        $this->assertTrue($response instanceof \Symfony\Component\HttpFoundation\JsonResponse);
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('ok', $data['status']);
        $this->assertArrayHasKey('version', $data);
        $this->assertNotEmpty($data['version']);

        $kernel->shutdown();
    }
}
