<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class Role extends Migrator
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
        $table = $this->table('role', ['comment' => "用户角色表", 'primary_key' => ['id']])->setCollation('utf8mb4_general_ci');
        $table->addColumn('role_name', 'string', ['comment' => '角色名称'])
            ->addColumn('role_desc', 'string', ['comment' => '描述'])
            ->addColumn('status', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'comment' => '状态', 'default' => 1])
            ->addIndex('role_name', ['unique' => true])->create();
        $this->up();
    }

    public function up()
    {
        $data = [
            ['id' => 1, 'role_name' => 'superadmin', 'role_desc' => '超级管理员', 'status' => 1],
            ['id' => 2, 'role_name' => 'teacher', 'role_desc' => '教师组', 'status' => 1],
            ['id' => 6, 'role_name' => 'student', 'role_desc' => '学生组', 'status' => 1],
            ['id' => 7, 'role_name' => 'test', 'role_desc' => '测试权限', 'status' => 1],
        ];
        $this->table('role')->insert($data)->saveData();
    }
}
