<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Test\TestCase\Controller\Component;

use Cake\Http\Exception\BadRequestException;
use Lqdt\CakephpApie\Test\Factory\AgentFactory;
use Lqdt\CakephpApie\Test\TestCase\ApiTestCase;

class ApiComponentContainTest extends ApiTestCase
{
    public function testAllow(): void
    {
        $controller = $this->getQueryController([
            'contain' => [['Clients']],
        ]);

        $this->expectException(BadRequestException::class);

        $agent = $controller->Api
            ->use('Agents')
            ->find();
    }

    public function testBasicContain(): void
    {
        $agents = AgentFactory::make(5)->withClients(null, 10)->persist();
        $controller = $this->getQueryController([
            'contain' => [['Clients']],
        ]);

        $agent = $controller->Api
            ->use('Agents')
            ->allow(['Clients'])
            ->find()
            ->first();

        /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface $agent */
        $this->assertNotEmpty($agent->clients);
        $this->assertEquals(10, count($agent->clients));
    }

    public function testClosureContain(): void
    {
        $agents = AgentFactory::make(5)->withClients(null, 10)->persist();
        $controller = $this->getQueryController([
            'contain' => [
                'Clients',
                '()' => [
                    'select' => [['Clients.id', 'Clients.agent_id', 'Clients.name']],
                    'order' => [['Clients.name' => 'ASC']],
                ],
            ],
        ]);

        $agents = $controller->Api
            ->use('Agents')
            ->allow('Clients')
            ->find()
            ->all();

        $this->assertNotEmpty($agents);
        foreach ($agents as $agent) {
            /** @var \Lqdt\CakephpApie\Test\Model\Entity\TestEntityInterface $agent */
            $this->assertNotEmpty($agent->clients);
            $this->assertEquals(10, count($agent->clients));
            $cur = null;
            foreach ($agent->clients as $client) {
                if (!$cur) {
                    $cur = $client;
                    continue;
                }

                $this->assertTrue(strcmp($cur->name, $client->name) <= 0);
                $cur = $client;
            }
        }
    }
}
