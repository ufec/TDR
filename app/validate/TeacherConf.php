<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class TeacherConf extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        "teacherId"  => ["number", "require"],
        "course"     => ["require", "array"],
        "headmaster" => ["require", "array"],
        "className"  => ["require", "array"],
        "department" => ["require", "matchDepart:/[\x{4e00}-\x{9fa5}0-9a-zA-Z（）()-，,、]+/u"],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        "teacherId.number"  => "ID字段非法！",
        "teacherId.require" => "未识别的用户！",
        "course.require" => "请传入课程！",
        "course.array" => "课程数据不规范！",
        "headmaster.require" => "班主任必须！",
        "headmaster.array" => "班主任数据不规范！",
        "className.require" => "班级必须！",
        "className.array" => "班级数据不规范！",
        "department.require" => "部门必须选择！",
        "department.alphaNum" => "部门非法字符！",
    ];

    /**
     * 自定义正则校验
     * @param string $value
     * @param string $rule
     * @return bool
     */
    protected function matchDepart(string $value, string $rule): bool
    {
        preg_match($rule, $value, $res);
        return (bool)$res;
    }
}
