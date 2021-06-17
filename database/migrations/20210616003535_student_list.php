<?php

use think\migration\Migrator;
use think\migration\db\Column;

class StudentList extends Migrator
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
        $table = $this->table('student_list', ['comment' => "学生列表", 'primary_key' => ['id']])->setCollation('utf8mb4_general_ci');
        $table->addColumn('name', 'string', ['comment'=>"姓名"])
            ->addColumn('class', 'string', ['comment'=>"班级ID"])
            ->addColumn('stu_num', 'string', ['comment'=>"学号"])
            ->addColumn('sex', 'string', ['comment'=>"性别"])
            ->addColumn('grade', 'integer', ['comment'=>"年级"])
            ->addIndex("stu_num", ['unique'=>true])->create();
    }
}
