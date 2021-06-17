<?php

use think\facade\Route;

// 系统管理
Route::group("system", function (){
    // 系统数据管理
    Route::group("data", function (){
        Route::post("importStudentData", "system.StudentDataManage/importStudentData"); // 导入学生数据
        Route::get('getStudentData', 'system.StudentDataManage/getStudentData'); // 获取学生数据
        Route::post('addStudent', 'system.StudentDataManage/addStudent'); // 新增学生
        Route::post('delStudent', 'system.StudentDataManage/delStudent'); // 删除学生
        Route::post('editStudent', 'system.StudentDataManage/editStudent'); // 编辑学生

        Route::post('queryDailyInfo', 'system.DailyDataManage/queryDailyInfo'); // 查询日报信息
        Route::post("importDepartmentData", 'system.DepartmentDataManage/importDepartmentData'); // 导入部门数据
        Route::get("getDepartmentData", 'system.DepartmentDataManage/getDepartmentData'); // 获取部门列表
        Route::post("addDepartment", 'system.DepartmentDataManage/addDepartment'); // 新增部门
        Route::post("editDepartment", 'system.DepartmentDataManage/editDepartment'); // 编辑部门
        Route::post("delDepartment", 'system.DepartmentDataManage/delDepartment'); // 删除部门
    });
    // 系统用户管理
    Route::group('user', function (){
        Route::get('getUserList', 'system.UserManage/getUserList'); // 获取用户列表
        Route::post('addUser', 'system.UserManage/addUser'); // 新增用户
        Route::post('editUser', 'system.UserManage/editUser'); // 修改用户
        Route::post('delUser', 'system.UserManage/delUser'); // 删除用户
        Route::post('changeUserStatus', 'system.UserManage/changeUserStatus'); // 修改用户状态
        Route::post('importUserData', 'system.UserManage/importUserData'); // 导入数据
    });
    // 系统权限管理
    Route::group('auth', function (){
        Route::get('getAuthGroup', 'system.AuthManage/getAuthGroup'); //获取权限列表
        Route::post('addAuth', 'system.AuthManage/addAuth'); // 新增/修改权限
        Route::post('delAuth', 'system.AuthManage/delAuth'); // 删除权限
        Route::post('setAuth', 'system.AuthManage/setAuth'); //设置权限
        Route::get('getAuth', 'system.AuthManage/getAuth');// 获取指定角色已有的权限
    });
    // 系统菜单管理
    Route::group('menu', function (){
        Route::get('getMenuList', 'system.MenuManage/getAllMenuList'); // 获取所有菜单列表
        Route::post('addMenu', 'system.MenuManage/addMenu'); // 新增/编辑菜单
        Route::post('delMenu', 'system.MenuManage/delMenu'); // 删除菜单
    });
})->middleware(['CheckToken', 'CheckApiAuth']);
Route::miss('Miss/Index');