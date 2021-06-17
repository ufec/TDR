<?php
declare (strict_types = 1);

namespace app\controller\system;

use app\model\User;
use app\model\UserRole;
use app\util\ReturnCode;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Model;
use think\Response;
use app\model\User as UserModel;
use app\model\UserRole as UserRoleModel;
use app\validate\UserManage as UserManageValidate;
use app\util\Tools;

class UserManage extends Base
{
    /**
     * 获取用户列表
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserList(): Response
    {
        $limit = request()->get('pageSize', 10, 'intval');
        $page = request()->get('page', 1, 'intval');
        $status = request()->get('status');
        $nickname = request()->get('nick_name', '', 'app\util\Tools::remove_space');
        $username = request()->get('username', '', 'intval');
        $userModel = new UserModel();
        $userModel = $userModel->where('id', '<>', 1);

        // 根据状态
        if ($status != null && (intval($status) === 0 || intval($status) === 1)){
            $userModel = $userModel->where('status', $status);
        }
        // 根据姓名
        if ($nickname) {
            $userModel = $userModel->whereLike('nick_name', "%$nickname%");
        }
        // 根据用户名
        if ($username){
            $userModel = $userModel->whereLike('username', "%$username%");
        }

        // 根据查询条件查出符合条件的所有用户，按创建时间降序排列
        $userList = $userModel->order('add_time', 'desc')
                              ->paginate(['page' => $page, 'list_rows' => $limit])
                              ->toArray();
        $userListInfo = $userList['data'];// 取出用户数据
        // 取出符合条件的所有用户的ID
        $userIdArr = array_column($userListInfo, 'id');
        // 根据ID查出该用户的角色
        $userRole = (new UserRoleModel())->whereIn('user_id', $userIdArr)->select();
        $userRoleList = $userRole->toArray();
        $userRoleList = Tools::change_arr_key($userRoleList, 'user_id');
        unset($userRole);
        foreach ($userListInfo as &$v){
            unset($v['password']);
            if (null != $v['last_login_ip']){
                $v['last_login_ip'] = long2ip($v['last_login_ip']);
            }
            if (isset($userRoleList[$v['id']])){
                $v['role_id'] = $userRoleList[$v['id']]['role_id'];
            }else{
                $v['role_id'] = "";
            }
        }
        unset($userRoleList);
        unset($v); // 删除引用防止造成后续赋值
        return $this->actionSuccess([
            'user_list'    => $userListInfo,
            'total'        => $userList['total'],
            'count'        => count($userList['data']),
            'page_size'    => $userList['per_page'],
            'current_page' => $userList['current_page'],
        ]);
    }

    /**
     * 新增用户
     * @return Response
     */
    public function addUser(): Response
    {
        $param = request()->param();
        try {
            validate(UserManageValidate::class)->check($param);
        } catch (ValidateException $e){
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $roles = []; // 权限组ID
        if (isset($param['checkPermissionGroup'])) {
            $roles = $param['checkPermissionGroup']; // 记录权限组(数据验证保证此项为数组)
            $roles = Tools::get_number_array($roles); // 过滤非数字元素
            unset($param['checkPermissionGroup']); // 移除非表字段
        }

        // 新增
        $param['add_time'] = (time() * 1000);
        $param['password'] = crypto_password($param['password']);
        $user = UserModel::create($param); // 新增用户
        if (!($user instanceof Model)){
            return $this->actionFailed(
                ReturnCode::DATABASE_ERROR,
                "添加用户失败！"
            );
        }
        $userId = $user->id; // 获取自增ID
        if ($roles){// 数组为真
            $role = implode(",", $roles); // 将权限组数组用逗号分割
            unset($roles);
            $userRole = UserRoleModel::create(['user_id' => $userId, 'role_id' => $role]);
            unset($userId);
            unset($role);
            if (!($userRole instanceof Model)){
                return $this->actionFailed(
                    ReturnCode::DATABASE_ERROR,
                    "添加权限组失败！"
                );
            }
        }
        return $this->actionSuccess();
    }

    /**
     * 编辑用户
     * @return Response
     */
    public function editUser(): Response
    {
        $param = request()->param(); // 获取数据
        // 数据验证
        try {
            validate(UserManageValidate::class)->scene('EditUser')->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        // 取数据
        try {
            $user = (new UserModel())->where('id', intval($param['id']))->find();
            $userRole = (new UserRoleModel())->where('user_id', intval($param['id']))->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        // 用户判空
        if ($user == null) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "未找到该用户！");
        }
        // 检查用户需要修改项
        if ($param['password']){
            $user->password = crypto_password($param['password']);
        }
        // 修改姓名
        if ($param['nick_name']){
            $user->nick_name = $param['nick_name'];
        }
        // 保存用户修改项
        $user->save();
        $roles = [];
        // 检查用户角色是否需要修改
        if (isset($param['checkPermissionGroup']) && is_array($param['checkPermissionGroup'])) {
            $roles = $param['checkPermissionGroup']; // 记录权限组(数据验证保证此项为数组)
            $roles = Tools::get_number_array($roles); // 过滤非数字元素
        }
        if (null == $userRole) {
            // 创建时没有角色，需要新建角色
            UserRoleModel::create(['user_id' => $user->id, 'role_id' => implode(",", $roles)]);
        }else {
            $userRole->role_id = implode(",", $roles);
            $userRole->save();
        }
        unset($userRole);
        unset($user);
        return $this->actionSuccess();
    }

    /**
     * 删除用户
     * @return Response
     */
    public function delUser(): Response
    {
        $param = request()->param();
        try {
            validate([
                "id" => ["require", "array"],
            ], [
                "id.require" => "未知用户！",
                "id.array" => "id类型错误！",
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        if (!is_array($param['id'])) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "id类型错误！");
        }
        $ids = Tools::get_number_array($param['id']);
        $delUser = UserModel::destroy($ids);
        $delUserRole = UserRoleModel::destroy(function ($query) use ($ids){
            $query->whereIn('user_id', $ids);
        });
        if (!$delUser || !$delUserRole) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, "删除失败！");
        }
        return $this->actionSuccess();
    }

    /**
     * 修改用户状态
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function changeUserStatus(): Response
    {
        $param = request()->param();
        try {
            validate(UserManageValidate::class)->scene("changeStatus")->check($param);
        }catch (ValidateException $e){
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $user = UserModel::find($param['id']);
        $user->status = $param['status'];
        if ($user->save()){
            return $this->actionSuccess();
        }else{
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, "修改失败");
        }
    }

    /**
     * 导入用户
     * @return Response
     */
    public function importUserData(): Response
    {
        $param = request()->param();
        if (empty($param)) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "参数无效！");
        }
        $insertData = [];
        $userModel = new UserModel();
        try {
            $userData = $userModel->field('username')->select()->toArray();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        $hasUserData = [];
        foreach ($userData as $item) {
            array_push($hasUserData, $item['username']);
        }
        unset($userData);
        for ($i = 0; $i < count($param); $i++){
            if (isset($param[$i]['username']) && !in_array($param[$i]['username'], $hasUserData) && isset($param[$i]['password']) && isset($param[$i]['nick_name'])) {
                array_push($insertData, [
                    'username' => $param[$i]['username'],
                    'password' => crypto_password($param[$i]['password']),
                    'nick_name' => $param[$i]['nick_name'],
                    'avatar_url' => "https://my-static.ufec.cn/other/avatar.webp",
                    'add_time' => time() * 1000
                ]);
            }
        }
        try {
            $userModel->saveAll($insertData);
        } catch (\Exception $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        return $this->actionSuccess();
    }
}
