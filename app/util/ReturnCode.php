<?php


namespace app\util;


class ReturnCode {

    const SUCCESS = 1; // 成功操作

    const PARAM_VALIDATE_ERROR = 1010001; // 请求参数验证失败

    const DATABASE_ERROR = 1010002; // 数据库操作失败

    const EMPTY_DATA_ERROR = 1010003; // 空数据错误

    const LOGIN_PASSWORD_ERROR = 1010004; // 密码错误

    const JWT_CHECK_TOKEN_ERROR = 1020001; // JWT Token验证失败

    const JWT_CHECK_TOKEN_EXPIRE_ERROR = 1020002; // JWT 已过期

    const IDENTITY_CHECK_ERROR = 1020003; // 身份校验失败

    const IDENTITY_NO_AUTH = 1020004; // 用户没有权限

    const LOGIN_STATUS_DISABLED = 1020005; // 账户被禁用

    const ROLE_MENU_IS_EMPTY = 1020006; // 角色菜单为空

    const SYSTEM_EXEC_JSON_ERROR = 2010001; // 处理json出错

    const SYSTEM_SET_CACHE_ERROR = 2020001; // 设置缓存出错

    const SYSTEM_EXEC_ERROR = 2030001; // 系统执行出错

    const UNDEFINED_ROUTE = 2040001; // 访问未定义路由

    const SYSTEM_UN_INSTALL =  2040002; // 系统还未安装

    public static function getConstants(): array {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}