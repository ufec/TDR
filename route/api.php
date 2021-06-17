<?php

use think\facade\Route;

Route::group('api', function (){
    Route::get('searchClass', 'api.Index/searchClass');// 搜索班级
    Route::get('getClassStudent', 'api.Index/getClassStudent'); // 获取班级下学生
    Route::post('searchStudent', 'api.Index/searchStudent'); // 搜索学生
    Route::get('getAllDepartment', 'api.Index/getAllDepartment'); // 获取所有部门信息
    Route::get("getAllCollege", "api.Index/getAllCollege"); // 获取所有学院
    Route::get("getAllClass", "api.Index/getAllClass"); // 获取所有班级
})->middleware(['CheckToken', 'CheckApiAuth']);