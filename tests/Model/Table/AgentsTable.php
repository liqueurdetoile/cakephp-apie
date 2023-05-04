<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Test\Model\Table;

use Cake\ORM\Table;

class AgentsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->hasMany('Clients');
    }
}
