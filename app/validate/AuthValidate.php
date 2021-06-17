<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class AuthValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'role_desc' => 'require|chsAlpha',
        'id' => 'number',
        'status' => 'number',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'role_name.require'  => '权限名称不能为空',
        'role_name.alphaNum' => '权限名称只能是字母或数字',
        'role_name.unique'   => "权限名称已存在，请重新输入",
        'role_desc.require'  => '权限描述不能为空',
        'role_desc.chsAlpha' => '权限描述只能为中文、字母或数字',
        'id.number'          => '主键非法！',
        'status.number'      => '状态非法！',
    ];

    protected $scene = [
        'editStatus' => ['id', 'status'],
    ];

    /**
     * 新增场景验证
     * @return AuthValidate
     */
    public function sceneAdd(): AuthValidate
    {
        return $this->only(['role_name', 'role_name'])
                    ->append('role_name', 'require|alphaNum|unique:role,role_name');
    }
}
