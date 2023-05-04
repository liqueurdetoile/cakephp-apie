<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Test\Model\Table;

use Cake\ORM\Table;

class ClientsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->belongsTo('Agents');
    }
}
