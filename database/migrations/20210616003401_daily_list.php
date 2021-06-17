<?php

use think\migration\Migrator;
use think\migration\db\Column;

class DailyList extends Migrator
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
        $table = $this->table('daily_list', ['comment' => "教师日报列表", 'primary_key' => ['id']])->setCollation('utf8mb4_general_ci');
        $table->addColumn('teacher_name', 'string', ['comment' => "教师姓名"])
            ->addColumn('department', 'string', ['comment' => "部门"])
            ->addColumn('class_room', 'string', ['comment' => "教室名"])
            ->addColumn('post_date', 'string', ['comment' => "上课日期"])
            ->addColumn('section', 'string', ['comment' => "教师姓名"])
            ->addColumn('true_attend_num', 'integer', ['comment' => "实到人数"])
            ->addColumn('attend_num', 'integer', ['comment' => "应到人数"])
            ->addColumn('use_media', 'string', ['comment' => "使用手机人数", 'null' => true])
            ->addColumn('un_image_student', 'string', ['comment' => "衣着问题学生列表", 'null' => true])
            ->addColumn('projector_damage', 'string', ['comment' => "投影仪问题列表", 'null' => true])
            ->addColumn('computer_damage', 'string', ['comment' => "电脑问题列表", 'null' => true])
            ->addColumn('other_warn_damage', 'string', ['comment' => "其他物品损坏列表", 'null' => true])
            ->addColumn('other_things', 'string', ['comment' => "其他说明", 'null' => true])
            ->addColumn('course', 'string', ['comment' => "课程"])
            ->addColumn('course_nature', 'string', ['comment' => "课程类型"])
            ->addColumn('class', 'string', ['comment' => "班级列表"])
            ->addColumn('head_teacher', 'string', ['comment' => "班主任列表"])
            ->addColumn('truancy_student', 'string', ['comment' => "旷课学生列表", 'null' => true])
            ->addColumn('late_student', 'string', ['comment' => "迟到学生列表", 'null' => true])
            ->addColumn('leave_early_student', 'string', ['comment' => "早退学生列表", 'null' => true])
            ->addColumn('leave_student', 'string', ['comment' => "请假学生列表", 'null' => true])
            ->addColumn('truancy_student_num', 'integer', ['comment' => "旷课学生人数", 'default' => 0])
            ->addColumn('late_student_num', 'integer', ['comment' => "迟到学生人数", 'default' => 0])
            ->addColumn('leave_early_student_num', 'integer', ['comment' => "早退学生人数", 'default' => 0])
            ->addColumn('leave_student_num', 'integer', ['comment' => "请假学生人数", 'default' => 0])
            ->addColumn('user_id', 'integer', ['comment' => "用户ID"])
            ->addColumn('submit_time', 'string', ['comment' => "提交时间"])
            ->addColumn('update_time', 'string', ['comment' => "更新时间", 'null' => true])
            ->create();
    }
}
