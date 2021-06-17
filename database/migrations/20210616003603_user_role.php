<?php

use think\migration\Migrator;
use think\migration\db\Column;

class UserRole extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('user_role', ['comment' => "用户对应角色表", 'primary_key' => ['id']])->setCollation('utf8mb4_general_ci');
        $table->addColumn('user_id', 'integer', ['comment'=>"用户ID"])
            ->addColumn('role_id', 'string', ['comment'=>'角色ID'])
            ->addIndex('user_id', ['unique'=>true])
            ->create();
        $this->up();
    }

    public function up()
    {
        $data = [ 'id' => 1, 'user_id' => 1, 'role_id' => 1 ];
        $this->insert('user_role', $data);
    }
}
