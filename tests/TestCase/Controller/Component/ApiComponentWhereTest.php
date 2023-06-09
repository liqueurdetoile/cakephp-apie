<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Test\TestCase\Controller\Component;

use Lqdt\CakephpApie\Test\Factory\ClientFactory;
use Lqdt\CakephpApie\Test\TestCase\ApiTestCase;

class ApiComponentWhereTest extends ApiTestCase
{
    public function testWhereWithArray(): void
    {
        /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface[] $clients */
        $clients = ClientFactory::make(20)->persist();
        $controller = $this->getQueryController([
            'where' => [
                ['id' => $clients[0]->id],
            ],
        ]);

        $q = $controller->Api
            ->use('Clients')
            ->find();

        $this->assertEquals(1, $q->count());

        /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface $c */
        $c = $q->first();
        $this->assertEquals($clients[0]->id, $c->id);
    }

    public function testWhereWithClosure(): void
    {
        /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface[] $clients */
        $clients = ClientFactory::make(20)->persist();
        $controller = $this->getQueryController([
            'where' => [
                '()' => [
                    'eq' => ['id', $clients[0]->id],
                ],
            ],
        ]);

        $q = $controller->Api
            ->use('Clients')
            ->find();

        $this->assertEquals(1, $q->count());

        /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface $c */
        $c = $q->first();
        $this->assertEquals($clients[0]->id, $c->id);
    }

    public function testWhereWithExpression(): void
    {
        /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface[] $clients */
        $clients = ClientFactory::make(20)->persist();
        $controller = $this->getQueryController([
            'where' => [
                'newExpr()' => [
                    'eq' => ['id', $clients[0]->id],
                ],
            ],
        ]);

        $q = $controller->Api
            ->use('Clients')
            ->find();

        $this->assertEquals(1, $q->count());

        /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface $c */
        $c = $q->first();
        $this->assertEquals($clients[0]->id, $c->id);
    }

    public function testWhereWithAdvancedExpression(): void
    {
        /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface[] $clients */
        $clients = ClientFactory::make(20)->persist();
        $controller = $this->getQueryController([
            'where' => [
                'newExpr()' => [
                    'or' => [
                        'newExpr()' => ['eq' => ['id', $clients[0]->id]],
                    ],
                    'add' => [
                        'newExpr()' => ['eq' => ['id', $clients[1]->id]],
                    ],
                ],
            ],
        ]);

        $q = $controller->Api
            ->use('Clients')
            ->find();

        $this->assertEquals(2, $q->count());
    }

    public function testWhereWithCumulatedExpression(): void
    {
        /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface[] $clients */
        $clients = ClientFactory::make(20)->persist();
        $controller = $this->getQueryController([
            'where' => [
                'newExpr()' => [
                    'or' => [
                        'newExpr()' => ['eq' => ['id', $clients[0]->id]],
                    ],
                    'add' => [
                        'newExpr()' => ['eq' => ['id', $clients[1]->id]],
                    ],
                    '+add' => [
                        'newExpr()' => ['eq' => ['id', $clients[2]->id]],
                    ],
                    '++add' => [
                        'newExpr()' => ['eq' => ['id', $clients[3]->id]],
                    ],
                ],
            ],
        ]);

        $q = $controller->Api
            ->use('Clients')
            ->find();

        $this->assertEquals(4, $q->count());
    }
}
