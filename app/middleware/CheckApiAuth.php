<?php
declare (strict_types = 1);

namespace app\middleware;

use app\util\Tools;
use app\util\ReturnCode;
use app\model\Menu as MenuModel;
use app\model\UserRole as UserRoleModel;
use app\model\RoleRule as RoleRuleModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;
use think\Response;

/**
 * 检测请求接口权限
 * Class CheckApiAuth
 * @package app\middleware
 */
class CheckApiAuth
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle(\think\Request $request, \Closure $next): Response
    {
        $identity = $request->jwt;
        $identityData = @json_decode(@base64_decode(@$identity['userInfo']->getValue()), true);
        if (empty($identityData)) {
            return json([
                "code" => ReturnCode::IDENTITY_CHECK_ERROR,
                "msg"  =>  "身份校验失败！",
                "data" => []
            ]);
        }
        $roleId = $identityData['role']['role_id'];
        $canIUse = $this->checkAuth(intval($identityData['id']), $roleId, $request->rule()->getRule());
        if ($canIUse){
            return $next($request);
        }else{
            return json([
                "code" => ReturnCode::IDENTITY_NO_AUTH,
                "msg"  =>  "您没有权限这么做！",
                "data" => []
            ]);
        }
    }

    private function checkAuth(int $uid, string $roleId, string $route): bool
    {
        $isAdmin = Tools::check_user_is_admin($uid);
        if ($isAdmin){
            return true;
        }else{
            $auth = $this->getUserAuth($roleId);
            return in_array($route, $auth);
        }

    }

    /**
     * @param string $roleId
     * @return array
     */
    private function getUserAuth(string $roleId): array
    {
        $authCache = Cache::get("auth_cache");
        if (!$authCache) {
            return [];
        }else{
            $roleIdArr = explode(",", $roleId);
            $auth = [];
            $cache = json_decode($authCache, true);
            foreach ($roleIdArr as $v){
                $v = intval($v);
                if (isset($cache[$v]) && is_array($cache[$v])){
                    foreach ($cache[$v] as $item){
                        $auth[] = $item['url'];
                    }
                    unset($cache[$v]);
                }
            }
            return array_unique($auth);
        }
    }
}