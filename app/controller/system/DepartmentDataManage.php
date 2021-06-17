<?php
declare(strict_types=1);

namespace app\controller\system;
use app\util\ReturnCode;
use app\model\Department;
use app\util\Tools;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Response;

class DepartmentDataManage extends Base
{
    /**
     * 导入部门
     * @return Response
     */
    public function importDepartmentData(): Response
    {
        $param = request()->param();
        try {
            validate([
                "department" => ["require", "array"],
            ], [
                "department.require" => "部门数据必须！",
                "department.array"  => "部门数据有误！"
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        // 去重
        $departments = array_unique($param['department']);
        // 排序
        sort($departments);
        try {
            $departmentData = (new Department())->field('name')->select();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if (!empty($departmentData)) {
            // 数据库中已有数据先去重
            $temp = [];
            foreach ($departmentData as $v) {
                $temp[] = $v->name;
            }
            $departmentData = $temp;
            unset($temp);
            $departments = array_diff($departments, $departmentData);
        }
        $insertData = [];
        foreach ($departments as $department) {
            if (preg_match("/[\x{4e00}-\x{9fa5}0-9a-zA-Z（）()-，,、]+/u", $department)) {
                array_push($insertData, ['name' => $department]);
            }
        }
        try {
            (new Department())->saveAll($insertData);
        } catch (\Exception $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        return $this->actionSuccess();
    }

    /**
     * 获取部门列表
     */
    public function getDepartmentData(): Response
    {
        $param = request()->param();
        try {
            validate([
                "page" => ["require", "number"],
                "pageSize" => ["require", "number"],
            ], [
                "page.require" => "page必须！",
                "page.number" => "page必须为数字！",
                "pageSize.require" => "pageSize必须！",
                "pageSize.number" => "pageSize必须！"
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        try {
            $department = (new Department())->order("id", "asc")->paginate(['page' => intval($param['page']), 'list_rows' => intval($param['pageSize'])]);
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if (null == $department) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "空数据！");
        }
        return $this->actionSuccess($department->toArray());
    }

    /**
     * 新增部门
     * @return Response
     */
    public function addDepartment(): Response
    {
        $param = request()->param();
        try {
            validate([
                "department" => ["require", "chsAlphaNum", "unique:department,name"],
            ], [
                "department.require" => "部门必须！",
                "department.chsAlpha" => "部门只能为汉字、字母或数字！",
                "department.unique" => "部门已存在！",
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $insert = Department::create(['name' => $param['department']]);
        if (!($insert instanceof Department)) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, "新增失败！");
        }
        return $this->actionSuccess();
    }

    /**
     * 编辑部门
     * @return Response
     */
    public function editDepartment(): Response
    {
        $param = request()->param();
        try {
            validate([
                "id" => ["require", "number"],
                "department" => ["require", "chsAlphaNum", "unique:department,name"],
            ], [
                "id.require" => "id必须！",
                "id.number"  => "id只能为数字！",
                "department.require" => "部门必须！",
                "department.chsAlpha" => "部门只能为汉字、字母或数字！",
                "department.unique" => "部门已存在！",
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        try {
            $department = (new Department())->where('id', intval($param['id']))->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        $department->name = $param['department'];
        if (!$department->save()) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, "修改失败!");
        }
        return $this->actionSuccess();
    }

    /**
     * 删除部门
     * @return Response
     */
    public function delDepartment(): Response
    {
        $param = request()->param();
        if (!is_array($param['id']) || empty($param['id'])) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "数据有误！");
        }
        $ids = Tools::get_number_array($param['id']);
        Department::destroy(function ($query) use ($ids) {
            $query->whereIn('id', $ids);
        });
        return $this->actionSuccess();
    }
}