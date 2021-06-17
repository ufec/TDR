<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Install extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('daily:install')
            ->setDescription('教学管理日报系统安装命令');
    }

    protected function execute(Input $input, Output $output)
    {
        $cacheType = config('cache.default');
        if ($cacheType == "redis" && !extension_loaded('redis')){
            $output->error("redis扩展未开启，请开启扩展，或修改位于 'config/cache.php' 的第9行，将 'redis' 改为 'file' ");
            exit();
        }
        $tplPath = root_path() . "install" . DIRECTORY_SEPARATOR;
        if (!is_dir($tplPath)){
            $output->error("目录结构不完整！install目录缺失，请重新获取源代码后重新执行php think daily:install");
            exit();
        }
        // 校验install目录是否可写
        if (!is_writable($tplPath)){
            $output->error("install目录不可写，请修改后重新执行php think daily:install");
            exit();
        }
        $lockFile = $tplPath . "lock.ini";
        $tplFile = $tplPath . "daily.tpl";
        // 校验文件是否存在
        if (!is_file($tplFile)){
            $output->error("目录结构不完整！install/daily.tpl文件缺失，请重新获取源代码后重新执行php think daily:install");
            exit();
        }
        if (is_file($lockFile)){
            $output->error("你已经安装过了！你可以删除install/lock.ini，重新执行php think daily:install");
            exit();
        }
        // 校验环境配置
        $this->checkEnv($output);
        $jwtFile = config_path() . "jwt.php";
        if (!is_file($jwtFile)){
            $output->error("JWT未配置，请先执行命令：php think jwt:create");
            exit();
        }
        $tempPath = runtime_path();
        // 校验缓存目录是否存在
        if (!is_dir($tempPath)){
            $output->error("目录结构不完整！runtime目录缺失，请补充后重新执行php think daily:install");
            exit();
        }
        // 校验runtime目录是否可写
        if (!is_writable($tempPath)){
            $output->error("runtime目录不可写，请修改后重新执行！");
            exit();
        }
        $randStr = get_rand_str(18); // 生成随机字符串
        $content = str_replace('${secret}', $randStr, file_get_contents($tplFile)); // 生成系统加密字符串
        file_put_contents(config_path() . "daily.php", $content); // 写文件
        file_put_contents($tplPath."lock.ini", 'user: daily, password: {$password}'); // 写安装锁文件
        $output->writeln("安装成功，请执行 php think migrate:run  命令来完成数据库配置安装");
    }

    /**
     * 校验环境配置
     * @param Output $output
     */
    protected function checkEnv(Output $output)
    {
        $map = [
            "DATABASE_DATABASE" => "数据库名称为空",
            "DATABASE_USERNAME" => "数据库用户名为空",
            "DATABASE_PASSWORD" => "数据库密码为空",
            "WECHAT_APPID"      => "微信小程序APPID为空",
            "WECHAT_APPSECRET"  => "微信小程序APPSECRET为空",
            "LANG_DEFAULT_LANG" => "默认语言未设置",
            "JWT_SECRET"        => "JWT未配置，请先执行命令：php think jwt:create",
        ];
        $env = env();
        foreach ($map as $k => $v){
            if (!isset($env[$k]) || !$env[$k]){
                $output->error($v);
                exit();
            }
        }
    }
}
