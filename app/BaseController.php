<?php
declare (strict_types = 1);

namespace app;

use app\model\Menu;
use app\model\RoleRule;
use app\util\Tools;
use think\App;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;
use think\Validate;
use think\Response;
use app\util\ReturnCode;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 成功操作
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return Response
     */
    protected function actionSuccess(array $data=[], string $msg="操作成功", int $code = ReturnCode::SUCCESS): Response
    {
        $res = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
        return json($res);
    }

    /**
     * 失败操作
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return Response
     */
    protected function actionFailed(int $code, string $msg="操作失败", array $data=[]): Response
    {
        $res = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
        return json($res);
    }

    /**
     * 获取AccessToken
     * @return string
     * @throws Exception
     */
    protected function getAccessToken(): string
    {
        $token = Cache::get("WX_AccessToken");
        if ($token){
            return $token;
        }
        $AppID = env("WECHAT_AppID");
        $AppSecret = env("WECHAT_AppSecret");
        $api = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$AppID&secret=$AppSecret";
        $jsonStr = file_get_contents($api);
        $data = json_decode($jsonStr, true);
        if (empty($data)){
            throw new Exception("微信接口请求失败！");
        }
        if (isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg']);
        }
        $access_token = $data['access_token'];
        $expires_in = intval($data['expires_in']);
        if (!Cache::set("WX_AccessToken", $access_token, $expires_in - 10)){
            throw new Exception("缓存设置失败！");
        }
        return $access_token;
    }

    /**
     * 更新权限缓存
     * @param array $authData
     * @return bool
     */
    protected function updateAuthCache(array $authData): bool
    {
        $allMenuDataCache = Cache::get('all_menu_data');
        // 能执行这个操作缓存一定存在，不存在说明非法访问！
        if (!$allMenuDataCache){
            return false;
        }
        // 从缓存中获取所有菜单
        $allMenuData = json_decode($allMenuDataCache, true);
        $auths = []; // 最终的权限
        $menus = []; // 最终的菜单
        foreach ($authData as $k => $v){
            if (!is_array($v)){
                continue;
            }
            // 指定键设置为数组
            $menus[$k] = [];
            $auths[$k] = [];
            foreach ($allMenuData as $menu){
                if (in_array($menu['id'], $v)){
                    if ($menu['router'] != ""){
                        // 路由
                        if (!in_array($menu, $menus[$k])){
                            $menus[$k][] = $menu;
                        }
                    }else if ($menu['url'] != ""){
                        // 权限
                        if (!in_array($menu, $auths[$k])){
                            $auths[$k][] = $menu;
                        }
                    }
                }
            }
        }
        foreach ($menus as $k => $menu){
            // 每一个角色都需要获取他的所有父级菜单
            $menus[$k] = $this->getAllFNode($allMenuData, $menu);
        }
        // 这两个缓存就可有可无了（超级管理员登陆是不会产生这两个缓存的）
        $roleAuthDataCache = Cache::get('auth_cache');
        $roleMenuDataCache = Cache::get('menu_cache');

        $roleAuthData = [];
        $roleMenuData = [];
        // 但这两个缓存必定同时存在
        if ($roleAuthDataCache && $roleMenuDataCache){
            // 获取数据
            $roleAuthData = json_decode($roleAuthDataCache, true);
            $roleMenuData = json_decode($roleMenuDataCache, true);
        }
        // 存在缓存就替换，不存在就新增
        foreach ($auths as $role => $auth){
            $roleAuthData[$role] = $auth;
        }
        foreach ($menus as $role => $menu){
            $roleMenuData[$role] = $menu;
        }
        $setAuthCache = Cache::set('auth_cache', json_encode($roleAuthData), 7200);
        $setMenuCache = Cache::set('menu_cache', json_encode($roleMenuData), 7200);
        return ($setAuthCache && $setMenuCache);
    }

    /**
     * 取出所有上一级菜单()
     * @param array $allMenuData // 全部菜单取出后剩下的菜单
     * @param array $menuData 在列表内且router不为空的菜单
     * @return array
     */
    protected function getAllFNode(array $allMenuData, array $menuData): array
    {
        $flag = 0;
        foreach ($menuData as $v){
            if ($v['fid'] != 0 && !isset($menuData[$v['fid']])){
                if (isset($allMenuData[$v['fid']])){
                    $flag = 1;
                    $menuData[$v['fid']] = $allMenuData[$v['fid']];
                    unset($allMenuData[$v['fid']]);
                }
            }
        }
        if ($flag){
            return $this->getAllFNode($allMenuData, $menuData);
        }else{
            return $menuData;
        }
    }

    /**
     * 更新菜单缓存（增/删/改后均需要执行）
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    protected function updateAllMenuCache(): bool
    {
        try {
            $allMenuData = (new Menu())->select()->toArray();
        } catch (DataNotFoundException $e) {
            throw new DataNotFoundException($e->getMessage());
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        } catch (DbException $e) {
            throw new DbException($e->getMessage());
        }
        return Cache::set('all_menu_data', $allMenuData, 7200);
    }
}
