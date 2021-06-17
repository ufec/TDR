<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;
use think\migration\db\Column;

class User extends Migrator
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
        $table = $this->table('user', ['comment' => "用户表", 'primary_key' => ['id']])->setCollation('utf8mb4_general_ci');
        $table->addColumn('username', 'string', ['comment' => "用户名"])
            ->addColumn('password', 'string', ['comment' => "密码"])
            ->addColumn('open_id', 'string', ['comment' => "微信开放ID", 'null' => true])
            ->addColumn('avatar_url', 'string', ['comment' => "头像地址", 'default' => 'https://my-static.ufec.cn/other/avatar.webp'])
            ->addColumn('nick_name', 'string', ['comment' => "姓名"])
            ->addColumn('last_login_time', 'string', ['comment' => "最后登录时间", 'null' => true])
            ->addColumn('last_login_ip', 'string', ['comment' => "最后登录IP", 'null' => true])
            ->addColumn('add_time', 'string', ['comment' => "创建时间"])
            ->addColumn('status', 'integer', ['limit'=>MysqlAdapter::INT_TINY, 'comment' => "用户状态", 'default' => 1])
            ->addIndex('username', ['unique'=>true])
            ->create();
        $this->up();
    }

    public function up()
    {
        $lockFilePath = root_path() . "install/lock.ini";
        $dailyConfPath = config_path() . "daily.php";
        if (!is_file($lockFilePath)){
            echo "未找到安装文件，请先执行 php think daily:install";
            exit();
        }
        if (!is_file($dailyConfPath)){
            echo "未找到系统配置文件，请先执行 php think daily:install";
            exit();
        }
        $password = get_rand_str(5);
        $cryptoPassword = crypto_password($password);
        $data = [
            'id' => 1,
            'username' => 'daily',
            'password' => $cryptoPassword,
            'open_id'  => '',
            'avatar_url' => 'https://my-static.ufec.cn/other/avatar.webp',
            'nick_name' => '超级管理员',
            'last_login_time' => '',
            'last_login_ip' => '',
            'add_time' => time() * 1000,
            'status' => 1,
        ];
        $this->insert('user', $data);
        file_put_contents($lockFilePath, str_replace('{$password}', $password, file_get_contents($lockFilePath)));
        file_put_contents($dailyConfPath, str_replace('${super_admin_id}', 1, file_get_contents($dailyConfPath)));
    }
}
