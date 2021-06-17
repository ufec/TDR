<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class Menu extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'name'      => ['chsAlpha', 'require', 'unique:menu,name'],
        'url'       => ['alphaLeftSlash:/^[\w+|\/][\w|\/]+\w$/'],
        'fid'       => ['require', 'number'],
        'router'    => ['alphaLeftSlash:/^[\w+|\/][\w|\/:]+\w$/', 'unique:menu,router'],
        'level'     => ['require', 'number'],
        'component' => ['alphaLeftSlash:/^[\w+|\/][\w|\/]+\w$/'],
        'icon'      => ['alphaDash'],
        'type'      => ['require', 'number'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        "id.require"  => "菜单ID不能为空",
        "id.number" => "菜单ID非法！",
        "name.require" => "菜单名称不能为空！",
        "name.chsAlpha"   => "菜单名称只能为汉字或字母！",
        "name.unique" => "菜单名已存在",
        "url.alphaLeftSlash" => "后端路由只能是字母或/",
        "fid.require" => "父级菜单不能为空！",
        "fid.number" => "父级菜单id只能为数字",
        "router.alphaLeftSlash" => "前端路由只能是字母或/",
        "router.unique" => "前端路由已存在！",
        "level.require" => "菜单级别必需",
        "level.number" => "菜单级别只能为数字",
        "component.alphaLeftSlash" => "前端组件只能是字母或/",
        "icon.alphaDash" => "图标只能为-或字母！",
        "type.require" => "菜单类型不能为空！",
        "type.number"  => "菜单类型只能为数字！",
    ];

    // 自定义验证规则
    protected function alphaLeftSlash(string $value, string $rule): bool
    {
        preg_match($rule, $value, $res);
        return (bool)$res;
    }

    // 自定义编辑验证场景
    public function sceneEdit(): Menu
    {
        return $this->append('id', 'require|number');
    }

    // 自定义新增顶级菜单场景
    public function sceneAddTopMenu(): Menu
    {
        return $this->remove('fid', 'require');
    }
}
