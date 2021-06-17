<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class StudentDataManageValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        "college" => ["require", "number"],
        "class" => ["require", "number"],
        "name" => ["require", "chs"],
        "sex" => ["require", "chs"],
        "stu_num" => ["require", "number", "unique:student_list,stu_num"],
        "grade" => ["require", "number", "length:4"],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        "college.require" => "学院必须选择！",
        "college.number" => "学院非法！",
        "class.require" => "班级必须选择！",
        "class.number" => "班级非法！",
        "name.require" => "姓名必须！",
        "name.chs" => "姓名只能为中文！",
        "sex.require" => "性别必须！",
        "sex.chs" => "性别非法！",
        "stu_num.require" => "学号必须！",
        "stu_num.number" => "学号非法！",
        "stu_num.unique" => "学号已存在！",
        "grade.length" => "年级非法！",
        "grade.require" => "年级必须！",
        "grade.number" => "年级非法！",
        "id.require" => "未找到该学生！",
        "id.number" => "非法字段id！"
    ];

    protected function sceneEdit(): StudentDataManageValidate
    {
        return $this->append("id", ["require", "number"]);
    }
}
