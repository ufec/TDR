<?php

use think\migration\Migrator;
use think\migration\db\Column;

class TeacherConf extends Migrator
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
        $table = $this->table('teacher_conf', ['comment' => "学生列表", 'primary_key' => ['id']])->setCollation('utf8mb4_general_ci');
        $table->addColumn('user_id', 'integer', ['comment' => "用户ID"])
            ->addColumn('course_name', 'string', ['comment' => "课程名称"])
            ->addColumn('class_id', 'string', ['comment' => "班级ID"])
            ->addColumn('head_teacher', 'string', ['comment' => "班主任"])
            ->addColumn('department', 'string', ['comment'=>"部门"])
            ->addIndex('user_id', ['unique'=>true])->create();
    }
}
