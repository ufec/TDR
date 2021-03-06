# TDR

教学管理日报系统（Teaching Daily Report）- 服务端
[网页端请移步至此](https://github.com/ufec/TDR-WEB)
[小程序端请移步至此](https://github.com/ufec/TDR-uniapp)

## 系统简介

- ThinkPHP 6.0.x
- 前后端分离设计
- 多用户权限管理
- 菜单管理
- 接口鉴权
- ......

学校需要对现有教师日报系统升级改造，于是便有了此项目，主要改写了界面，但保留了原有基本布局，优化了教师填写体验

部分代码参照其他开源项目而来，取之于人，用之与众！

感谢 `thans/tp-jwt-auth` 这个项目封装好的中间件 `jwt` ，简化了不少操作

很多地方在刚开始设计的时候没想完善，导致写的时候增加了不少负担！！！

慢慢完善吧！

[接口文档点击此处](https://docs.apipost.cn/preview/9351867c6436f3a6/9bc7b97088fd5b35) ， 接口太多了，会持续更新接口文档

## 安装

获取源代码

```shell
git clone https://github.com/ufec/TDR
```

安装依赖

```shell
composer install
```

以上两步可以简化为一步

```shell
composer create-project ufec/tdr
```

重命名环境配置文件

```shell
copy .\.env.example .env
```

### 安装程序

初始化`jwt`

```shell
php think jwt:create
```

配置安装

```shell
php think daily:install
```

>你必须先创建一个数据库，名为 **`daily`**,编码为 **`utf8mb4_general_ci`**

数据库迁移命令

```shell
php think migrate:run
```

> 注：以上命令必须依次执行，若先执行数据库迁移，则必须删除所有数据表，重新执行

等待命令执行完毕即可，后台 **账号密码** 在根目录下 **`install/lock.ini`** 文件中

## 配置说明

```text
APP_DEBUG = true

[APP]
DEFAULT_TIMEZONE = Asia/Shanghai

[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = daily
USERNAME = root
PASSWORD = root
HOSTPORT = 3306
CHARSET = utf8mb4
DEBUG = true
PREFIX = daily_

[WECHAT]
AppID = 
AppSecret = 

[LANG]
DEFAULT_LANG = zh-cn

```

默认配置应该如上所示

`WECHAT` 部分用于微信小程序配置项，前往[微信小程序后台](https://mp.weixin.qq.com/wxamp/devprofile/get_profile)获取

`DATABASE` 部分按实际填写

初始化之后会有 `JWT` 部分，默认只有 `SECRET` 配置项，`JWT` 默认有效期为 `60` 秒，如需自定义配置，在 `SECRET` 后另起一行 `TTL=时长` 单位：秒

## 目录结构

```text
|-- TDR
    |-- app                   应用目录
    |   |-- command             命令行
    |   |-- controller          控制器
    |   |   |-- api                 接口模块
    |   |   |-- daily               日报模块
    |   |   |-- system              系统模块
    |   |   |-- user                用户模块
    |   |-- middleware          中间件
    |   |-- model               模型
    |   |-- util                工具
    |   |-- validate            数据验证
    |-- config                  系统配置
    |-- database
    |   |-- migrations          迁移文件所在目录
    |-- extend
    |-- install               安装目录
    |-- public
    |   |-- static
    |-- route                 路由文件
    |-- runtime               
    |-- vendor                
```

## 系统需求

- php >= 7.1
- Mysql >= 5.7
- Redis

## 鸣谢

- 感谢开源框架 [ThinkPHP V6.0.*](https://packagist.org/packages/topthink/think)，提供简单易用的`PHP`框架
- 感谢 `GVP` 开源项目 [ApiAdmin](https://gitee.com/apiadmin/ApiAdmin)，部分写法参照此项目实现
- `thans/tp-jwt-auth` 提供的 `jwt` 支持