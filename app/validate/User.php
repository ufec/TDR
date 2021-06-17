<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class User extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'username' => 'require|alphaNum',
        'password' => 'require|alphaNum',
        'nick_name' => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'username.require' => "用户名不能为空",
        'username.alphaNum'=> "用户名只能为数字或字母",
        'password.require' => "密码不能为空",
        'password.alphaNum'=> "密码只能为数字或字母",
        'nick_name.require' => "姓名必须",
    ];

    /**
     * 定义验证场景
     * 格式 '场景名' => ['字段一', '字段二', .....]
     *
     * @var array
     */
    protected $scene = [
        'login'  =>  ['username','password'],
    ];
}
