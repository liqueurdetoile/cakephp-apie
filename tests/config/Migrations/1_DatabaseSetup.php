<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class DatabaseSetup extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $this->table('agents', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('name', 'string', ['null' => true, 'length' => 100])
            ->create();

        $this->table('clients', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('agent_id', 'uuid', ['null' => true])
            ->addForeignKey('agent_id', 'agents', 'id', ['delete' => 'NO_ACTION', 'update' => 'NO_ACTION'])
            ->addColumn('name', 'string', ['null' => false, 'length' => 100])
            ->addColumn('vip', 'boolean', ['null' => true])
            ->addColumn('register', 'date', ['null' => true])
            ->addColumn('last_login', 'datetime', ['null' => true])
            ->addColumn('visits', 'integer', ['null' => true])
            ->addColumn('buyings', 'float', ['null' => true])
            ->create();
    }
}
