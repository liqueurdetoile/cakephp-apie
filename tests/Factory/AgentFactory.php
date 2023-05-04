<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Test\Factory;

use CakephpFixtureFactories\Factory\BaseFactory;
use Faker\Generator;

class AgentFactory extends BaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     *
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return 'Agents'; // PascalCase of the factory's table.
    }

    /**
     * Defines the default values of you factory. Useful for
     * not nullable fields.
     * Use the patchData method to set the field values.
     * You may use methods of the factory here
     *
     * @return void
     */
    protected function setDefaultTemplate(): void
    {
          $this->setDefaultData(function (Generator $faker) {
               return [
                    'id' => $faker->uuid,
                    'name' => $faker->name,
               ];
          });
    }

    /**
     * @param \Closure|\Cake\Datasource\EntityInterface|array|int|string|null $parameter Entity parameter
     * @param int $n Number of entity
     * @return self
     */
    public function withClients($parameter = null, int $n = 1): self
    {
        return $this->with('Clients', ClientFactory::make($parameter, $n));
    }
}
