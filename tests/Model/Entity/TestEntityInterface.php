<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Test\Model\Entity;

use Cake\Datasource\EntityInterface;

/**
 * @property string $id
 * @property string $name
 * @property int $count
 * @property TestEntityInterface[] $clients
 * @property TestEntityInterface[] $agents
 */
interface TestEntityInterface extends EntityInterface
{
}
