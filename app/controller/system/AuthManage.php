<?php
declare (strict_types = 1);

namespace app\controller\system;

use app\util\Tools;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Cache;
use think\Model;
use think\Response;
use app\model\Role as RoleModel;
use app\model\RoleRule as RoleRuleModel;
use app\model\UserRole;
use app\util\ReturnCode;
use app\validate\AuthValidate;
use app\controller\user\Login;

class AuthManage extends Base
{
    /**
     * 获取系统所有权限组
     * @return Response
     */
    public function getAuthGroup(): Response
    {
        try {
            if (request()->get('status') == 1){
                $roles = (new RoleModel())->where('status', '=', 1)->select()->toArray();
            }else{
                $roles = (new RoleModel())->select()->toArray();
            }
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(
                ReturnCode::DATABASE_ERROR,
                $e->getMessage()
            );
        }
        return $this->actionSuccess($roles);
    }

    /**
     * 新增、修改权限组
     * @return Response
     */
    public function addAuth(): Response
    {
        $param = request()->param();
        // 新增或修改用户组信息
        if (count($param) != 2 || !isset($param['id']) || !isset($param['status'])){
            try {
                validate(AuthValidate::class)->scene('Add')->check($param);
            }catch (ValidateException $e){
                return $this->actionFailed(
                    ReturnCode::PARAM_VALIDATE_ERROR,
                    $e->getMessage()
                );
            }
        }else{
            // 只存在id和status说明是更新用户组状态
            try {
                validate(AuthValidate::class)->scene('editStatus')->check($param);
            }catch (ValidateException $e){
                return $this->actionFailed(
                    ReturnCode::PARAM_VALIDATE_ERROR,
                    $e->getMessage()
                );
            }
        }
        // 含有主键更新操作
        if (isset($param['id'])) {
            try {
                $role = RoleModel::find($param['id']);
            } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
                return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
            }
            if (isset($param['role_desc'])){
                $role->role_desc = $param['role_desc'];
            }
            if (isset($param['status'])) {
                $role->status = $param['status'];
            }
            if ($role->save()){
                return $this->actionSuccess();
            }else{
                return $this->actionFailed(ReturnCode::DATABASE_ERROR);
            }
        }else{
            // 不含有主键新增操作
            $role = RoleModel::create($param);
            if (!($role instanceof Model)){
                return $this->actionFailed(ReturnCode::DATABASE_ERROR, "新增数据失败！");
            }
            return $this->actionSuccess();
        }
    }

    /**
     * 删除权限组
     * @return Response
     */
    public function delAuth(): Response
    {
        $id = request()->param('id');
        if ($id != intval($id)) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "参数错误！");
        }
        try {
            $userRole = (new UserRole())->where("find_in_set($id, role_id)")->select();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if ($userRole) {
            // 删除所有用户权限中包含此权限的记录
            foreach ($userRole as $v){
                $allRoleId = explode(",", $v->role_id);
                unset($allRoleId[array_search($id, $allRoleId)]);
                $newData = implode(',', $allRoleId);
                $v->role_id = $newData;
                unset($newData);
                unset($allRoleId);
                $v->save();
            }
        }
        $del_role = RoleModel::destroy($id);
        $del_user_auth = RoleRuleModel::destroy(['role_id' => $id]);
        if ($del_role && $del_user_auth){
            return $this->actionSuccess();
        }else{
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, "删除失败！");
        }
    }

    /**
     * 设置权限
     * @return Response
     */
    public function setAuth(): Response
    {
        $param = request()->param();
        try {
            validate(['id' => 'require|number', 'auth' => 'array'], [
                'id.require' => '参数错误!',
                'id.number'  => 'ID只能为数字!',
                'auth.array' => 'auth只能是数组！',
            ])->check($param);
        } catch (ValidateException $e){
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $role_rule = new RoleRuleModel();
        try {
            // 根据传入的角色id获取对应的权限id
            $record = $role_rule->where('role_id', intval($param['id']))->field('auth_id')->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        $auth = $param['auth']; // 获取前端传入的权限id(数组)
        $auth = Tools::get_number_array($auth); // 过滤掉非数字的元素
        sort($auth); // 排序
        // save机制
        if (null == $record){
            // 未找到该角色的权限 新增
            $res = $role_rule->save([
                'role_id' => $param['id'],
                'auth_id' => implode(",", $auth)
            ]);
        }else{
            // 找到就修改
            $res = $record->save([
                'role_id' => $param['id'],
                'auth_id' => implode(",", $auth)
            ]);
        }
        if ($res) {
            $authData[intval($param['id'])] = $auth;
            $updateCache = $this->updateAuthCache($authData);
            if ($updateCache) {
                return $this->actionSuccess();
            }
            return $this->actionFailed(ReturnCode::SYSTEM_SET_CACHE_ERROR, "更新缓存失败！");
        }else{
            return $this->actionFailed(ReturnCode::DATABASE_ERROR);
        }
    }

    /**
     * 获取指定角色已有的权限
     * @return Response
     */
    public function getAuth(): Response
    {
        $id = request()->get('id', 0);
        if ($id != intval($id)) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "参数错误！");
        }
        try {
            $auth_data = (new RoleRuleModel())->where('role_id', $id)->field('auth_id')->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR);
        }
        if (null == $auth_data) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "数据为空！");
        }
        $auth_id = explode(",", $auth_data->toArray()['auth_id']);
        return $this->actionSuccess($auth_id);
    }
}