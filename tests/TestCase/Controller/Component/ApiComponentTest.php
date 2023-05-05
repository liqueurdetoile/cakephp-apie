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

    public function isAllowedData(): array
    {
        return [
            ['Childs.Subchilds', true],
            ['Childs.Subchild', true], // This will fail during query but is valid substring
            ['Childs', true],
            ['Users', false],
            ['Childs.Otherchilds', false],
            ['Childs.Subchilds.Users', false],
            ['Childs.Users.Subchilds', false],
        ];
    }

    /** @dataProvider isAllowedData */
    public function testIsAllowed(string $path, bool $expected): void
    {
        $controller = $this->getQueryController([]);

        $controller->Api->allow('Childs.Subchilds');

        $this->assertEquals($expected, $controller->Api->isAllowed($path));
    }
}
