<?php
declare (strict_types = 1);

namespace app\controller;
use app\BaseController;
use app\util\ReturnCode;
use think\Exception;
use think\Response;
use think\facade\App;

class Miss extends BaseController
{
    public function Index(): Response
    {
        $msg = "操作失败！";
        $code = ReturnCode::UNDEFINED_ROUTE;
        $lockFile = root_path() . "install/lock.ini";
        if (!is_file($lockFile)){
            $msg = "系统尚未初始化，请在终端执行 php think daily:install";
            $code = ReturnCode::SYSTEM_UN_INSTALL;
        }
        return $this->actionFailed($code, $msg, [
            'ThinkPHP Version' => App::version(),
            'version' => '1.0',
        ]);
    }
}
