<?php
declare (strict_types = 1);

namespace app\controller\user;

use app\model\Role;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;
use think\Response;
use app\model\User as UserModel;
use app\model\Menu as MenuModel;
use app\model\UserRole as UserRoleModel;
use app\model\RoleRule as RoleRuleModel;
use app\model\Role as RoleModel;
use app\validate\User;
use app\util\ReturnCode;
use app\util\Tools;
use thans\jwt\facade\JWTAuth;


class Login extends Base
{
    /**
     * 用户登录接口
     * @return Response
     */
    public function login(): Response
    {
        $ip = request()->ip(); // 获取用户IP
        $param = request()->param(); // 获取其他参数
        if (isset($param['wchatLogin'])) {
            // 微信登陆
            try {
                validate([
                    "code" => ["require"],
                    "encryptedData" => ["require"],
                    "iv" => ['require'],
                    "rawData" => ["require"],
                    "signature" => ["require"],
                ])->check($param['wchatLogin']);
            } catch (ValidateException $e) {
                return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
            }
            $param['wchatLogin']['rawData'] = htmlspecialchars_decode($param['wchatLogin']['rawData']);
            $rawData = json_decode($param['wchatLogin']['rawData'], true);
            if (!$rawData){
                return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "用户信息有误！");
            }
            try {
                $openid = $this->getOpenId($param['wchatLogin']['code'], $param['wchatLogin']['rawData'], $param['wchatLogin']['signature']);
                $user = (new UserModel())->where("open_id", $openid)->find();
            } catch (Exception $e) {
                return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, $e->getMessage());
            }
            if (null == $user){
                return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "未找到您的数据，请先使用账号登陆，再点击绑定微信即可");
            }
        }else{
            // 普通账号密码登录
            // 验证数据
            try {
                validate(User::class)->scene('login')->check($param);
            }catch (ValidateException $e){
                return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
            }
            // 根据账号查询数据
            try {
                $user = (new UserModel())->where('username', $param['username'])->find();// 当前用户模型
            } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
                return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
            }
            // 空数据
            if (null == $user){
                return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "用户不存在！");
            }
            // 验证密码
            if ($user->password != crypto_password($param['password'])){
                return $this->actionFailed(ReturnCode::LOGIN_PASSWORD_ERROR, "密码错误！");
            }
            try {
                $this->menuModel = (new MenuModel())->select();
            } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
                return $this->actionFailed(
                    ReturnCode::DATABASE_ERROR,
                    $e->getMessage()
                );
            }
        }
        return $this->actionLogin($user, $ip);
    }

    /**
     * 二维码登录 前端轮询
     * @return Response
     */
    public function checkScanQRCode(): Response
    {
        $ip = request()->ip(); // 获取用户IP
        $param = request()->param();
        // 校验参数
        try {
            validate([
                "uuid" => ["require", "length:32"],
            ], [
                "uuid.require" => "参数有误！",
                "uuid.length" => "参数有误！",
            ])->check($param);
        } catch (ValidateException $e){
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $token = Cache::get($param['uuid']."_token");
        if (!$token){
            // 没有找到也返回成功，避免轮询一直弹窗，前端根据data来判断即可
            return $this->actionSuccess();
        }
        try {
            $user = (new UserModel())->where('open_id', $token)->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if (null == $user){
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "未找到该用户！");
        }
        Cache::delete($param['uuid']."_token");
        return $this->actionLogin($user, $ip);
    }

    /**
     * 绑定微信
     * @return Response
     */
    public function bindWechat(): Response
    {
        $param = request()->param();
        try {
            validate([
                "code" => ["require"],
                "encryptedData" => ["require"],
                "iv" => ['require'],
                "rawData" => ["require"],
                "signature" => ["require"],
                "userId" => ["require", "number"]
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        try {
            $user = (new UserModel())->where("id", intval($param['userId']))->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        if(null == $user) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "未找到该用户！");
        }
        if ($user->open_id){
            return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, "已经绑定过了哦！");
        }
        $param['rawData'] = htmlspecialchars_decode($param['rawData']);
        try {
            $openid = $this->getOpenId($param['code'], $param['rawData'], $param['signature']);
        } catch (Exception $e) {
            return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, $e->getMessage());
        }
        $user->open_id = $openid;
        $rawData = json_decode($param['rawData'], true);
        if (!$rawData){
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "用户信息有误！");
        }
        $user->avatar_url = $rawData['avatarUrl'];
        if (!$user->save()){
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, "绑定失败！");
        }
        return $this->actionSuccess([], "绑定成功！");
    }

    /**
     * 用户登出
     * @return Response
     */
    public function logout(): Response
    {
        $token = JWTAuth::token()->get();
        $identityStr = request()->jwt['userInfo']->getValue();
        $identity = @json_decode(@base64_decode($identityStr), true);
        Cache::delete("userId_".$identity['id']);
        JWTAuth::invalidate($token);
        return $this->actionSuccess([], "退出成功！");
    }

    /**
     * 刷新用户Token
     * @return Response
     */
    public function refreshToken(): Response
    {
        $token = JWTAuth::refresh();
        return $this->actionSuccess(['token' => $token]);
    }

    /**
     * 获取用户菜单接口
     * @return Response
     */
    public function getUserRoute(): Response
    {
        // 身份校验
        if (!isset(request()->jwt) || !is_array(request()->jwt)){
            return $this->actionFailed(
                ReturnCode::IDENTITY_CHECK_ERROR,
                "身份校验失败！"
            );
        }
        $identityStr = request()->jwt['userInfo']->getValue();
        $identity = json_decode(base64_decode($identityStr), true);
        // 空数组/false/null都会触发错误
        if (!$identity || !is_array($identity)){
            return $this->actionFailed(ReturnCode::SYSTEM_EXEC_JSON_ERROR);
        }
        $menuDataCache = Cache::get('menu_cache');
        if ($menuDataCache){
            $menuData = json_decode($menuDataCache, true);
            $menu = [];
            foreach ($menuData as $item){
                if (is_array($item)){
                    foreach ($item as $value){
                        if (!in_array($value, $menu)){
                            $menu[] = $value;
                        }
                    }
                }
            }
            $allMenuData = json_decode(Cache::get('all_menu_data'), true);
            $menu = $this->getAllFNode($allMenuData, $menu);
            $menu = Tools::list_to_tree($menu);
        }else {
            try {
                $menu = $this->getRoleInfo(intval($identity['id']), $identity['role']['role_id']);
            } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
                return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
            }
        }
        return $this->actionSuccess($menu);
    }

    /**
     * 真正执行登录操作，封装起来，便于多场景调用
     * @param $user
     * @param $ip
     * @return Response
     */
    private function actionLogin(UserModel $user, $ip): Response
    {
        // 判断用户是否为管理员
        $isAdmin = Tools::check_user_is_admin($user->id);
        if (!$isAdmin) {
            // 非管理员验证用户状态
            if ($user->status != 1) {
                return $this->actionFailed(ReturnCode::LOGIN_STATUS_DISABLED, "账户被禁用，请联系管理员！");
            }
        }
        // 更新数据
        $user->last_login_ip = ip2long($ip);
        $user->last_login_time = (time() * 1000);
        $user->save();
        // 用户私密信息不传出
        unset($user->password);
        try {
            // 获取用户菜单
            $menu = $this->getRoleInfo(intval($user->id), $user->role->role_id);
            // 获取用户权限
            $auth = $this->getRoleInfo(intval($user->id), $user->role->role_id, "auth");
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if (empty($menu)){
            return $this->actionFailed(ReturnCode::ROLE_MENU_IS_EMPTY, "没有分配给您任何权限，前联系管理员授权使用");
        }
        // 获取用户角色
        try {
            $role = $this->getUserRole($user->id);
        } catch (Exception $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }

        // 设置Token
        $userInfo = base64_encode(json_encode($user->toArray()));
        $token = JWTAuth::builder(['userInfo' => $userInfo]);
        $jwtTTL = env('JWT_TTL') ?: config('jwt.ttl');
        $expire = (time() + intval($jwtTTL)) * 1000;
        $data=[
            'user'  => $user->toArray(),
            'auth'  => $auth,
            'token' => $token,
            'menu'  => $menu,
            'role'  => $role,
            'token_expire' => $expire,
        ];
        return $this->actionSuccess($data, "登陆成功，欢迎回来！");
    }

    /**
     * 获取某个(或多个)角色的所有菜单
     * @param string $roleId
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getRoleAllMenu(string $roleId): array
    {
        // 兼容一角色多用户的情况
        $roleIdArr = explode(",", $roleId);
        $roleIdArr = Tools::get_number_array($roleIdArr); // 去除非数字元素
        try {
            // 根据角色ID找权限ID
            $authArr = (new RoleRuleModel())->whereIn('role_id', $roleIdArr)->select();
        } catch (DataNotFoundException $e) {
            throw new DataNotFoundException($e->getMessage());
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        } catch (DbException $e) {
            throw new DbException($e->getMessage());
        }
        $allMenu = [];
        foreach ($authArr as $item){
            if ($item == null){
                continue;
            }
            $allMenu[$item->role_id] = (new MenuModel())->whereIn("id", $item->auth_id)->select()->toArray();
        }
        return $allMenu;
    }

    /**
     * 获取某个(或多个)角色菜单
     * @param int $uid 用户ID
     * @param string $roleId 角色id
     * @param string $type 类型|menu代表获取前端展示菜单，auth代表接口菜单
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function getRoleInfo(int $uid, string $roleId, string $type="menu"): array
    {
        $roleIdArr = explode(",", $roleId);
        $roleIdArr = Tools::get_number_array($roleIdArr); // 去除非数字元素
        // 取所有菜单数据
        $allMenuDataCache = Cache::get('all_menu_data');
        if ($allMenuDataCache){
            $allMenuData = json_decode($allMenuDataCache, true);
        }else{
            $allMenuData = (new MenuModel())->select()->toArray();
            Cache::set('all_menu_data', json_encode($allMenuData), 7200);
        }
        if (Tools::check_user_is_admin($uid)){
            // 管理员拥有所有权限
            $menus = [];
            foreach ($allMenuData as $menuData){
                if ($type === "auth"){
                    if ($menuData['url'] != '' && !in_array($menuData, $menus)){
                        $menus[] = $menuData;
                    }
                }else{
                    if ($menuData['router'] != ""){
                        $menus[$menuData['id']] = $menuData;
                    }
                }
            }
        }else{
            // 非管理员根据角色来决定
            if ($type === "auth"){
                // 类型为接口权限
                $cache = Cache::get('auth_cache');
            }else{
                // 类型为普通菜单
                $cache = Cache::get('menu_cache');
            }

            $roleMenuData = [];
            if ($cache){
                // 有缓存
                $cacheData = json_decode($cache, true);
                foreach ($roleIdArr as $k => $v){
                    // 缓存中存在
                    if (in_array($v, $cacheData)){
                        $roleMenuData[$v] = $cacheData[$v]; // 取出缓存中的数据
                        unset($roleIdArr[$k]); // 删掉已保存过的数据
                    }
                }
                $roleId = implode(",", $roleIdArr); // 剩余不在缓存中的数据，去数据库查
            }
            if ($roleId && count($roleIdArr) > 0){
                try {
                    $allMenu = $this->getRoleAllMenu($roleId);
                } catch (DataNotFoundException $e) {
                    throw new DataNotFoundException($e->getMessage());
                } catch (ModelNotFoundException $e) {
                    throw new ModelNotFoundException($e->getMessage());
                } catch (DbException $e) {
                    throw new DbException($e->getMessage());
                }
                foreach ($roleIdArr as $v) {
                    // 不存在的数据跳过
                    if (!isset($allMenu[$v])){
                        continue;
                    }
                    $temp = [];
                    foreach ($allMenu[$v] as $index => $item){
                        if ($type === "auth"){
                            // 类型为接口权限
                            if (isset($item['url']) && $item['url'] != ''){
                                $temp[$item['id']] = $item;
                                unset($allMenu[$index]);
                            }
                        }else{
                            // 类型为普通菜单
                            if (isset($item['router']) && $item['router'] != ''){
                                $temp[$item['id']] = $item;
                                unset($allMenu[$index]);
                            }
                        }

                    }
                    unset($allMenu[$v]);
                    $roleMenuData[$v] = $temp;
                    unset($temp);
                }
            }
            if ($type === "auth"){
                // 类型为接口权限
                Cache::set('auth_cache', json_encode($roleMenuData), 7200);
            }else{
                // 类型为普通菜单
                Cache::set('menu_cache', json_encode($roleMenuData), 7200);
            }
            $menus = [];
            foreach ($roleMenuData as $roleMenu){
                if (is_array($roleMenu) && !empty($roleMenu)){
                    foreach ($roleMenu as $k => $v){
                        if (!in_array($v, $menus)){
                            $menus[] = $v;
                            unset($roleMenu[$k]);
                        }
                    }
                }
            }
            $menus = $this->getAllFNode($allMenuData, $menus);
        }
        if ($type === "auth"){
            // 类型为接口权限
            $auth = [];
            foreach ($menus as $menu){
                $auth[] = $menu['url'];
            }
            return array_unique($auth);
        }else{
            // 类型为普通菜单
            return Tools::list_to_tree($menus);
        }
    }

    /**
     * 获取用户角色
     * @throws Exception
     */
    private function getUserRole(int $uid): array
    {
        try {
            // 根据用户ID取出所属全部权限组id
            $userRoleIdModel = (new UserRoleModel())->where('user_id', $uid)->field('role_id')->find();
            $roleModel = (new RoleModel())->whereIn('id', $userRoleIdModel->role_id)->field('role_name')->select();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            throw new Exception($e->getMessage());
        }
        $role = [];
        foreach ($roleModel as $value) {
            $role[] = $value->role_name;
        }
        return $role;
    }

    /**
     * 获取用户openid
     * @param string $code
     * @param string $rawData
     * @param string $signature
     * @return string
     * @throws Exception
     */
    private function getOpenId(string $code, string $rawData, string $signature):string
    {
        $AppID = env("WECHAT_AppID");
        $AppSecret = env("WECHAT_AppSecret");
        $api = "https://api.weixin.qq.com/sns/jscode2session?appid=$AppID&secret=$AppSecret&js_code=$code&grant_type=authorization_code";
        $json = file_get_contents($api);
        $json_data = json_decode($json, true);
        if (!isset($json_data['openid']) || !isset($json_data['session_key'])){
            throw new Exception("调用微信接口失败！");
        }
        $openid = $json_data['openid'];
        $session_key = $json_data['session_key'];
        if (sha1($rawData.$session_key) != $signature){
            throw new Exception("数据不完整，请稍等几秒重试！");
        }
        return $openid;
    }
}