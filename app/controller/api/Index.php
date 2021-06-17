<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\StudentClass;
use app\model\StudentCollege;
use app\model\StudentList;
use app\model\TeacherConf;
use app\model\Department;
use app\util\ReturnCode;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Response;

class Index extends Base
{
    /**
     * 搜索班级
     * @return Response
     */
    public function searchClass(): Response
    {
        $param = request()->param();
        try {
            validate(['className' => 'chsAlphaNum', 'className.chsDash' => "班级名称只是汉字、字母或数字"])->check($param);
        } catch (ValidateException $e){
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $studentClassModel = new StudentClass();
        if (isset($param['className'])) {
            $className = $param['className'];
            $studentClassModel = $studentClassModel->whereRaw('INSTR(name, "'.$className.'") > 0');
        }
        try {
            $studentClassModel = $studentClassModel->select();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        $searchData = $studentClassModel->toArray();
        if (empty($searchData)) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "未找到该班级，请重新输入！");
        }
        return $this->actionSuccess($searchData);
    }

    /**
     * 获取班级学生
     */
    public function getClassStudent(): Response
    {
        $id = request()->get('id');
        if (!$id || $id != intval($id)) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR);
        }
        try {
            $teacherConfClass = (new TeacherConf())->where('user_id', $id)->field('class_id')->find();
            if (null == $teacherConfClass) {
                return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "未找到该教师教学配置！");
            }
            $classInfo = (new StudentClass())->whereIn('id', $teacherConfClass->class_id)->select();
            if (null == $classInfo) {
                return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "本学年尚未配置班级！");
            }
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        $data = [];
        foreach ($classInfo as $item) {
            $studentInfo = [];
            foreach ($item->student as $value) {
                array_push($studentInfo, [
                    'label' => $value->name,
                    'value' => $value->name
                ]);
            }
            array_push($data, [
                'label' => $item->name,
                'value' => $item->name,
                'children' => $studentInfo,
            ]);
        }
        return $this->actionSuccess($data);
    }

    /**
     * 搜索学生
     * @return Response
     */
    public function searchStudent(): Response
    {
        $param = request()->param();
        try {
            validate([
                "class" => "array", "name" => "require|chs"
            ], [
                "class.array"   => "班级错误！",
                "name.require"  => "姓名必须！",
                "name.chs"      => "姓名不合法！",
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $studentList = new StudentList();
        if (isset($param['class']) && !empty($param['class'])) {
            $classIds = [];
            foreach ($param['class'] as $v) {
                $tempId = null;
                if(isset($v['key'])){
                    $tempId = $v['key'];
                }
                if (isset($v['value'])){
                    $tempId = $v['value'];
                }
                if (null != $tempId) {
                    $classIds[] = $tempId;
                }
            }
            $studentList = $studentList->whereIn('class', $classIds);
        }
        try {
            $studentDataModel = $studentList->whereLike('name', "%" . $param['name'] . "%")->select();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if ($studentDataModel->isEmpty()) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "未找到该学生！");
        }
        $data = [];
        foreach ($studentDataModel as $item) {
            array_push($data, [
                'class_name' => $item->class_info->name,
                'id' => $item->id,
                'name' => $item->name,
                'stu_num' => $item->stu_num,
            ]);
        }
        return $this->actionSuccess($data);
    }

    /**
     * 获取所有部门
     * @return Response
     */
    public function getAllDepartment(): Response
    {
        try {
            $department = (new Department())->select()->toArray();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if (empty($department)) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "数据为空！");
        }
        return $this->actionSuccess($department);
    }

    /**
     * 获取所有学院
     * @return Response
     */
    public function getAllCollege(): Response
    {
        try {
            $collegeList = (new StudentCollege())->select()->toArray();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if (empty($collegeList)) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "学院数据为空！");
        }
        return $this->actionSuccess($collegeList);
    }

    /**
     * 获取所有班级
     * @return Response
     */
    public function getAllClass(): Response
    {
        try {
            $classList = (new StudentClass())->select()->toArray();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if (empty($classList)) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "班级数据为空！");
        }
        return $this->actionSuccess($classList);
    }
}
