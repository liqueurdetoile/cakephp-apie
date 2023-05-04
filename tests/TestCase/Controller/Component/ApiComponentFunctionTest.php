<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Test\TestCase\Controller\Component;

use Lqdt\CakephpApie\Test\Factory\ClientFactory;
use Lqdt\CakephpApie\Test\TestCase\ApiTestCase;

class ApiComponentFunctionTest extends ApiTestCase
{
    public function testFunction(): void
    {
        ClientFactory::make(20)->persist();
        $controller = $this->getQueryController([
            'select' => [
                ['count' => [
                    'func()' => [
                        'count' => ['*'],
                    ],
                ]],
            ],
        ]);

        /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface $r */
        $r = $controller->Api
            ->use('Clients')
            ->find()
            ->first();

        $this->assertEquals(20, $r->count);
    }
}
