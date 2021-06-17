<?php

use think\facade\Route;

Route::group('daily', function (){
    Route::post('setTeacherConf', 'daily.TeacherConf/editTeacherConf'); // 设置教学配置
    Route::get('getTeacherConf', 'daily.TeacherConf/getTeacherConf'); // 获取教师配置
    Route::post('submit', 'daily.Daily/addDaily'); // 新增日报
    Route::get('list', 'daily.Daily/listDaily'); // 日报列表
    Route::get('dailyInfo', 'daily.Daily/dailyInfo'); // 日报详情
    Route::post('editDaily', 'daily.Daily/editDaily'); // 编辑日报
    Route::post('delDaily', 'daily.Daily/delDaily'); // 删除日报
})->middleware(['CheckToken', 'CheckApiAuth']);