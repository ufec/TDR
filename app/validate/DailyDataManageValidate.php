<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class DailyDataManageValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        "startTime" => ["number", "length:13"],
        "endTime" => ["number", "length:13"],
        "teacherName" => ["chs"],
        "page" => ["require", "number"],
        "pageSize" => ["require", "number"],
        "department" => ["matchDepart:/[\x{4e00}-\x{9fa5}0-9a-zA-Z（）()-，,、]+/u"],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        "startTime.number" => "时间非法！",
        "startTime.length" => "时间非法！",
        "endTime.number" => "时间非法！",
        "endTime.length" => "时间非法！",
        "teacherName.chs" => "教师姓名只能为中文！",
        "page.number" => "page只能为数字",
        "page.require" => "page必须！",
        "pageSize.number" => "pageSize只能为数字",
        "pageSize.require" => "pageSize必须！",
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
