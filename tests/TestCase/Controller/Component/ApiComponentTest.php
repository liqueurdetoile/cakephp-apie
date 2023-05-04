<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Test\TestCase\Controller\Component;

use Lqdt\CakephpApie\Controller\Component\ApiComponent;
use Lqdt\CakephpApie\Test\TestCase\ApiTestCase;

class ApiComponentTest extends ApiTestCase
{
    public function testComponentLoading(): void
    {
        $controller = $this->getQueryController([]);
        $this->assertInstanceOf(ApiComponent::class, $controller->Api);
    }
}
