<?php

use think\migration\Migrator;
use think\migration\db\Column;

class StudentCollege extends Migrator
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
        $table = $this->table('student_college', ['comment' => "学院列表", 'primary_key' => ['id']])->setCollation('utf8mb4_general_ci');
        $table->addColumn('name', 'string', ['comment'=>"学院名称"])
            ->create();
    }
}
