<?php

use think\facade\Route;

Route::group('user', function (){
    Route::post('login', 'user.Login/login'); // 登录接口
    Route::get('logout', 'user.Login/logout')->middleware(['CheckToken']); // 登出接口
    Route::get('refreshToken', 'user.Login/refreshToken')->middleware(['CheckToken']); // 刷新Token接口
    Route::get('getUserRoute', 'user.Login/getUserRoute')->middleware(['CheckToken', 'CheckApiAuth']); // 获取用户菜单接口
    Route::post('changeUserInfo', 'user.Index/changeUserInfo')->middleware(['CheckToken']); // 修改用户名接口

    Route::post('bindWechat', 'user.Login/bindWechat')->middleware(['CheckToken']); // 绑定微信用户
    Route::post('getQRCode', 'user.Index/getQRCode'); // 生成小程序码，无需认证
    Route::post('checkUUID', 'user.Index/checkUUID')->middleware(['CheckToken']); // 校验用户UUID
    Route::post('checkScanQRCode', 'user.Login/checkScanQRCode'); // 扫码登录校验 前端轮询，无需认证
    Route::miss('Miss/Index');
});