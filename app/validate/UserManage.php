<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class UserManage extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'username' => 'require|alphaNum|length:1,10|unique:user,username',
        'password' => 'require|alphaNum',
        'checkPermissionGroup' => 'array',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'username.require' => "用户名必须",
        'username.alphaNum' => "用户名只能是数字或字母.",
        'username.length' => "用户名长度为1-10",
        'username.unique' => "用户名已存在",

        'password.require' => "密码必须",
        'password.alphaNum' => "密码只能是数字或字母",

        'checkPermissionGroup.array' => "权限组只能是数组",

        'id.number' => "主键非法！",
        'id.require' => "主键必须！",

        'status.number' => "状态非法！",
        'status.require' => "状态必须！"
    ];

    protected $scene = [];

    public function sceneChangeStatus(): UserManage
    {
        return $this->only(['id', 'status'])
                    ->append('id', 'require|number')
                    ->append('status', 'require|number');
    }

    public function sceneEditUser(): UserManage
    {
        return $this->remove('password', 'require')
                    ->append('id', 'require|number');
    }
}
