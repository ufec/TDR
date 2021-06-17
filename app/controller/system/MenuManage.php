<?php
declare (strict_types=1);

/**
 * FileName: MenuManage.php
 * User: Ufec https://github.com/ufec
 * Date: 2021/4/11
 * Time: 18:15
 */

namespace app\controller\system;

use app\model\RoleRule;
use app\util\ReturnCode;
use app\util\Tools;
use app\model\Menu as MenuModel;
use app\validate\Menu as MenuValidate;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\ValidateException;
use think\Response;
/**
 * 菜单管理
 * Class MenuManage
 * User: Ufec https://github.com/ufec
 * Date: 2021/4/11
 * Time: 18:19
 * @package app\controller\system
 */
class MenuManage extends Base
{
    /**
     * 获取菜单列表
     * @return Response
     */
    public function getAllMenuList(): Response
    {
        $param = request()->param();
        $menuModel = new MenuModel();
        if (isset($param['menuName'])){
            try {
                validate(['menuName' => 'chsAlpha'], ['menuName.chsAlpha' => '菜单名称只能是汉字或字母'])->check($param);
            } catch (ValidateException $e){
                return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
            }
            $menuModel = $menuModel->whereLike('name', "%".$param['menuName']."%");
        }
        try {
            $menuList = $menuModel->select()->toArray();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        $menu = Tools::list_to_tree($menuList);
        if (!$menu){
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "数据为空！");
        }
        return $this->actionSuccess($menu);
    }

    /**
     * 新增/编辑菜单
     * @return Response
     */
    public function addMenu(): Response
    {
        $param = request()->param();
        $fid = intval($param['fid']);
        $isEdit = false;
        try {
            if (array_key_exists('id', $param) && is_numeric($param['id'])) {
                // 编辑
                validate(MenuValidate::class)->scene('Edit')->check($param);
                $isEdit = !$isEdit;
            }else{
                // 新增
                if ($param['type'] == 0) {
                    validate(MenuValidate::class)->scene('AddTopMenu')->check($param);
                }else{
                    validate(MenuValidate::class)->check($param);
                }
            }
        } catch (ValidateException $e) {
            return $this->actionFailed(
                ReturnCode::PARAM_VALIDATE_ERROR,
                $e->getMessage()
            );
        }
        if ($isEdit) {
            // 编辑
            try {
                $menuModel = (new MenuModel)->where('id', $param['id'])->find();
            } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
                return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
            }
            // 父级菜单改变了，考虑level变化
            if ($fid != $menuModel->fid){
                try {
                    $fid_level = (new MenuModel())->where('id', $fid)->field('level')->find()['level'];
                } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
                    return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
                }
                $param['level'] = $fid_level + 1;
            }
            $update = $menuModel->allowField(['name', 'url', 'fid', 'router', 'level', 'component', 'icon'])->save($param);
            if ($update){
                return $this->actionSuccess();
            }else{
                return $this->actionFailed(ReturnCode::DATABASE_ERROR, "修改失败！");
            }
        }else{
            // 新增
            if ($fid != 0){
                try {
                    $fid_level = (new MenuModel())->where('id', $fid)->field('level')->find();
                } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
                    return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
                }
                $fid_level = $fid_level['level'];
                $param['level'] = $fid_level + 1;

                // 修改权限
                $prevMenuId = $this->getAllPrevMenu($fid);
                array_push($prevMenuId, $fid);
                try {
                    // 取出所有角色组的所有权限ID
                    $roles = (new RoleRule())->select();
                } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
                    return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
                }
                $authInfo = [];
                foreach ($roles as $role) {
                    $authInfo[$role->id] = explode(",", $role->auth_id);
                }
                try {
                    $this->removeAuth($authInfo, $prevMenuId);
                } catch (Exception $e) {
                    return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
                }
            }
            $menuModel = MenuModel::create($param);
            try {
                $this->updateAllMenuCache();
            } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
                return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
            }
            if (($menuModel instanceof MenuModel) && $menuModel->id){
                return $this->actionSuccess();
            }else{
                return $this->actionFailed(ReturnCode::DATABASE_ERROR);
            }
        }
    }

    /**
     * 删除菜单
     * @return Response
     */
    public function delMenu(): Response
    {
        /**
         * 删除逻辑：
         * 如果是父级菜单，先删除所有子菜单，再删除父级菜单，保留所有需要删除的菜单的id，更新权限组权限
         * 如果是子菜单直接删除，保留删除的菜单ID，更新权限组权限
         *      删除子菜单需要考虑包含此菜单的权限组id，涉及到前端是否全选，（全选会多一个父级菜单ID）
         *
         */
        $param = request()->param();
        // 数据验证
        try {
            validate([
                "id" => ["require", "number"],
                "fid" => ["require", "number"],
                "level" => ["require", "number"],
            ], [
                "id.require"  => "菜单ID必须！",
                "id.number"   => "非法菜单ID！",
                "fid.require" => "父菜单ID必须！",
                "fid.number"  => "非法父菜单ID",
                "level.require"  => "菜单ID必须！",
                "level.number"   => "非法菜单ID！",
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $id = intval($param['id']);
        $fid = intval($param['fid']);
        $level = intval($param['level']);
        unset($param);

        try {
            // 验证当前传入数据与数据库数据是否匹配
            $menu = (new MenuModel())->where('id', $id)->find();
            if ($menu->fid !== $fid || $menu->level !== $level) {
                return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "非法菜单组合！");
            }
            // 取出所有角色组的所有权限ID
            $roles = (new RoleRule())->select();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }

        // 所有的权限组的权限（以角色id为键，权限id数组为值）
        $authInfo = [];
        foreach ($roles as $role) {
            $authInfo[$role->id] = explode(",", $role->auth_id);
        }
        $delMenuIdArr = []; // 要删除的所有菜单ID
        $nextMenuIdArr = $this->getAllMenu((string)$id); // 取出当前菜单的所有子菜单
        if ($level === 1) {
            // 顶级菜单 处理方法
            $delMenuIdArr = array_merge($nextMenuIdArr, $delMenuIdArr);
        }else{
            $prevMenuId = $this->getAllPrevMenu($id);
            $delMenuIdArr = array_merge($nextMenuIdArr, $prevMenuId);
        }
        array_push($delMenuIdArr, $id);
        if (!empty($delMenuIdArr)) {
            try {
                $this->removeAuth($authInfo, $delMenuIdArr);
            } catch (Exception $e) {
                return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
            }
        }
        MenuModel::destroy($delMenuIdArr);// 删除所有菜单
        try {
            $this->updateAllMenuCache();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        return $this->actionSuccess();
    }

    /**
     * 修改权限
     * @param array $authInfo 所有的权限组权限信息
     * @param array $delMenuIdArr 需要移除的权限
     * @return bool
     * @throws Exception
     */
    private function removeAuth(array $authInfo, array $delMenuIdArr): bool
    {
        $changeAuthRes = false;
        foreach ($authInfo as $k => $v) {
            $diffArr = array_diff($v, $delMenuIdArr); // 当前权限组ID中删除掉要该删除的菜单
            sort($diffArr); // 排序
            $authInfo[$k] = $diffArr; // 重新赋值，准备更新数据库信息
        }
        try {
            $roles = (new RoleRule())->whereIn('id', array_keys($authInfo))->select();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            throw new Exception($e->getMessage());
        }
        foreach ($roles as $role) {
            $role->auth_id = implode(",", $authInfo[$role->id]);
            $changeAuthRes = $role->save();
        }
        return $changeAuthRes;
    }

    /**
     * 找出菜单的所有子菜单（无限级）
     * @param string $id
     * @return array
     */
    private function getAllMenu(string $id): array
    {
        static $delMenuIdArr = []; // 静态数组防止被递归重新赋值
        try {
            $menuIdArr = (new MenuModel())->whereIn('fid', $id)->field('id')->select()->toArray();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return [];
        }
        if (!empty($menuIdArr)){
            $temp = []; // 临时变量
            for ($i=0; $i<count($menuIdArr); $i++){
                array_push($delMenuIdArr, $menuIdArr[$i]['id']);
                array_push($temp, $menuIdArr[$i]['id']);
            }
            $ids = implode(",", $temp);
            unset($temp);
            return $this->getAllMenu($ids);
        }else{
            return $delMenuIdArr; // 递归出口
        }
    }

    /**
     * 取出所有父级菜单ID（无限级）包含顶级元素 top
     * @param int $id
     * @return array
     */
    private function getAllPrevMenu(int $id): array
    {
        static $prevMenuId = [];
        try {
            $menu = (new MenuModel())->where('id', $id)->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return [];
        }
        if (null == $menu) {
            // 没找到该数据，返回空数组
            return [];
        }
        if ($menu->fid == 0) {
            // 该数据为顶级菜单递归出口
//            $prevMenuId['top'] = $menu->id; // 指出谁是顶级菜单
            return $prevMenuId;
        } else {
            array_push($prevMenuId, $menu->fid);
            return $this->getAllPrevMenu($menu->fid);
        }
    }
}