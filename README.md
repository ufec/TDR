# TDR

教学管理日报系统（Teaching Daily Report）- 服务端
[网页端请移步至此](https://github.com/ufec/TDR-WEB)
[小程序端请移步至此](https://github.com/ufec/TDR-uniapp)

## 安装

获取源代码

```shell
git clone https://github.com/ufec/TDR
```

重命名环境配置文件

```shell
copy .\.env.example .env
```

安装依赖

```shell
composer install
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