<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Test\Factory;

use CakephpFixtureFactories\Factory\BaseFactory;
use Faker\Generator;

class ClientFactory extends BaseFactory
{
    /**
     * Defines the Table Registry used to generate entities with
     *
     * @return string
     */
    protected function getRootTableRegistryName(): string
    {
        return 'Clients'; // PascalCase of the factory's table.
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
     * @return self
     */
    public function withAgents($parameter = null): self
    {
        return $this->with('Agents', AgentFactory::make($parameter));
    }
}
