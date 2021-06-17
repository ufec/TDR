<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class Menu extends Migrator
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
        $table = $this->table('menu', ['comment' => "菜单表", 'primary_key' => ['id']])->setCollation('utf8mb4_general_ci');
        $table->addColumn('name', 'string', ['comment' => "菜单名称"])
            ->addColumn('url', 'string', ['comment' => "后端路由地址" ,'null'=>true])
            ->addColumn('fid', 'integer', ['comment' => "父级菜单id" ,'null'=>true])
            ->addColumn('router', 'string', ['comment' => "前端路由" ,'null'=>true])
            ->addColumn('level', 'integer', ['comment' => "菜单层级" ,'null'=>true])
            ->addColumn('component', 'string', ['comment' => "前端组件" ,'null'=>true])
            ->addColumn('icon', 'string', ['comment' => "菜单图标" ,'null'=>true])
            ->addColumn('type', 'integer', ['comment' => "菜单类型" ,'null'=>true])
            ->addColumn('show', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 1, 'comment' => "是否显示菜单"])
            ->addIndex(['name', 'url', 'router'], ['unique' => true])->create();
        $this->up();
    }

    public function up()
    {
        $data = [
            ['id' => 1, 'name' => '仪表盘', 'url' => '', 'fid' => 0, 'router' => 'dashboard', 'level' => 1, 'component' => '', 'icon' => 'dashboard', 'type' => 0, 'show' => 1],
            ['id' => 2, 'name' => '工作台', 'url' => '', 'fid' => 1, 'router' => 'workplace', 'level' => 2, 'component' => 'dashboard/workplace', 'icon' => 'desktop', 'type' => 1, 'show' => 1],
            ['id' => 3, 'name' => '教师日报', 'url' => '', 'fid' => 0, 'router' => 'teacher', 'level' => 1, 'component' => '', 'icon' => 'team', 'type' => 0, 'show' => 1],
            ['id' => 4, 'name' => '填写日报', 'url' => '', 'fid' => 3, 'router' => 'dailySubmit', 'level' => 2, 'component' => 'teacher/dailySubmit', 'icon' => 'form', 'type' => 1, 'show' => 1],
            ['id' => 5, 'name' => '查看日报', 'url' => '', 'fid' => 3, 'router' => 'dailyList', 'level' => 2, 'component' => 'teacher/dailyList', 'icon' => 'unordered-list', 'type' => 1, 'show' => 1],
            ['id' => 6, 'name' => '教学配置', 'url' => '', 'fid' => 3, 'router' => 'teacherConf', 'level' => 2, 'component' => 'teacher/teacherConf', 'icon' => 'setting', 'type' => 1, 'show' => 1],
            ['id' => 7, 'name' => '系统管理', 'url' => '', 'fid' => 0, 'router' => 'system', 'level' => 1, 'component' => '', 'icon' => 'setting', 'type' => 0, 'show' => 1],
            ['id' => 8, 'name' => '用户管理', 'url' => '', 'fid' => 7, 'router' => 'userManage', 'level' => 2, 'component' => '', 'icon' => 'team', 'type' => 1, 'show' => 1],
            ['id' => 9, 'name' => '权限管理', 'url' => '', 'fid' => 7, 'router' => 'authManage', 'level' => 2, 'component' => '', 'icon' => 'key', 'type' => 1, 'show' => 1],
            ['id' => 10, 'name' => '菜单管理', 'url' => '', 'fid' => 7, 'router' => 'menuManage', 'level' => 2, 'component' => '', 'icon' => 'menu', 'type' => 1, 'show' => 1],
            ['id' => 11, 'name' => '数据管理', 'url' => '', 'fid' => 7, 'router' => 'dataManage', 'level' => 2, 'component' => '', 'icon' => 'database', 'type' => 1, 'show' => 1],
            ['id' => 12, 'name' => '日报数据', 'url' => '', 'fid' => 11, 'router' => 'dailyDataManage', 'level' => 3, 'component' => '', 'icon' => 'bar-chart', 'type' => 1, 'show' => 1],
            ['id' => 13, 'name' => '学生数据', 'url' => '', 'fid' => 11, 'router' => 'studentDataManage', 'level' => 3, 'component' => '', 'icon' => 'area-chart', 'type' => 1, 'show' => 1],
            ['id' => 14, 'name' => '系统接口', 'url' => '', 'fid' => 0, 'router' => '', 'level' => 1, 'component' => '', 'icon' => '', 'type' => 0, 'show' => 1],
            ['id' => 15, 'name' => '用户登录', 'url' => 'user/login', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 16, 'name' => '用户登出', 'url' => 'user/logout', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 17, 'name' => '刷新用户Token', 'url' => 'user/refreshToken', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 18, 'name' => '获取用户菜单', 'url' => 'user/getUserRoute', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 19, 'name' => '获取学生数据', 'url' => 'system/data/getStudentData', 'fid' => 13, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 20, 'name' => '获取用户列表', 'url' => 'system/user/getUserList', 'fid' => 8, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 21, 'name' => '新增用户', 'url' => 'system/user/addUser', 'fid' => 8, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 22, 'name' => '修改用户状态', 'url' => 'system/user/changeUserStatus', 'fid' => 8, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 23, 'name' => '获取权限列表', 'url' => 'system/auth/getAuthGroup', 'fid' => 9, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 24, 'name' => '新增修改权限', 'url' => 'system/auth/addAuth', 'fid' => 9, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 25, 'name' => '获取所有菜单列表', 'url' => 'system/menu/getMenuList', 'fid' => 10, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 26, 'name' => '新增编辑菜单', 'url' => 'system/menu/addMenu', 'fid' => 10, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 27, 'name' => '删除菜单', 'url' => 'system/menu/delMenu', 'fid' => 10, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 28, 'name' => '编辑日报', 'url' => '', 'fid' => 3, 'router' => 'dailyEdit/:id', 'level' => 2, 'component' => '', 'icon' => 'edit', 'type' => 1, 'show' => 1],
            ['id' => 29, 'name' => '设置教学配置', 'url' => 'daily/setTeacherConf', 'fid' => 6, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 30, 'name' => '获取教师配置', 'url' => 'daily/getTeacherConf', 'fid' => 6, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 31, 'name' => '新增日报', 'url' => 'daily/submit', 'fid' => 4, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 32, 'name' => '获取日报列表', 'url' => 'daily/list', 'fid' => 5, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 33, 'name' => '获取日报详情', 'url' => 'daily/dailyInfo', 'fid' => 28, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 34, 'name' => '编辑日报内容', 'url' => 'daily/editDaily', 'fid' => 28, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 35, 'name' => '删除日报', 'url' => 'daily/delDaily', 'fid' => 28, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 36, 'name' => '搜索班级', 'url' => 'api/searchClass', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 37, 'name' => '获取班级下学生', 'url' => 'api/getClassStudent', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 38, 'name' => '搜索学生', 'url' => 'api/searchStudent', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 39, 'name' => '修改用户名', 'url' => 'user/changeUserName', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 40, 'name' => '查询日报信息', 'url' => 'system/data/queryDailyInfo', 'fid' => 12, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 41, 'name' => '导入学生数据', 'url' => 'system/data/importStudentData', 'fid' => 13, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 42, 'name' => '删除权限', 'url' => 'system/auth/delAuth', 'fid' => 9, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 43, 'name' => '设置权限', 'url' => 'system/auth/setAuth', 'fid' => 9, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 44, 'name' => '获取指定角色已有的权限', 'url' => 'getAuth', 'fid' => 9, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 45, 'name' => '部门数据', 'url' => '', 'fid' => 11, 'router' => 'departmentDataManage', 'level' => 3, 'component' => '', 'icon' => 'usergroup-delete', 'type' => 1, 'show' => 1],
            ['id' => 46, 'name' => '导入部门数据', 'url' => 'system/data/importDepartmentData', 'fid' => 45, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 47, 'name' => '获取部门列表', 'url' => 'system/data/getDepartmentData', 'fid' => 45, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 48, 'name' => '新增部门', 'url' => 'system/data/addDepartment', 'fid' => 45, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 49, 'name' => '编辑部门', 'url' => 'system/data/editDepartment', 'fid' => 45, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 50, 'name' => '删除部门', 'url' => 'system/data/delDepartment', 'fid' => 45, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 51, 'name' => '获取所有部门', 'url' => 'api/getAllDepartment', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 52, 'name' => '获取所有班级', 'url' => 'api/getAllClass', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 53, 'name' => '获取所有学院', 'url' => 'api/getAllCollege', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 54, 'name' => '新增学生', 'url' => 'system/data/addStudent', 'fid' => 13, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 55, 'name' => '删除学生', 'url' => 'system/data/delStudent', 'fid' => 13, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 56, 'name' => '编辑学生', 'url' => 'system/data/editStudent', 'fid' => 13, 'router' => '', 'level' => 4, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 57, 'name' => '删除用户', 'url' => 'system/user/delUser', 'fid' => 8, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 58, 'name' => '导入数据', 'url' => 'system/user/importUserData', 'fid' => 8, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 59, 'name' => '编辑用户', 'url' => 'system/user/editUser', 'fid' => 8, 'router' => '', 'level' => 3, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 60, 'name' => '绑定微信', 'url' => 'user/bindWechat', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 61, 'name' => '生成小程序码', 'url' => 'user/getQRCode', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 62, 'name' => '校验用户UUID', 'url' => 'user/checkUUID', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
            ['id' => 63, 'name' => '扫码登录校验', 'url' => 'user/checkScanQRCode', 'fid' => 14, 'router' => '', 'level' => 2, 'component' => '', 'icon' => '', 'type' => 2, 'show' => 1],
        ];
        $this->table('menu')->insert($data)->saveData();
    }
}
