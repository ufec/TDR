<?php
declare (strict_types=1);

namespace app\controller\system;

use app\model\StudentList;
use app\util\ReturnCode;
use app\util\Tools;
use app\model\StudentClass as StudentClassModel;
use app\model\StudentCollege as StudentCollegeModel;
use app\model\StudentList as StudentListModel;
use app\validate\StudentDataManageValidate;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Db;
use think\Response;


class StudentDataManage extends Base
{
    /**
     * 获取学生数据
     * @return Response
     */
    public function getStudentData(): Response
    {
        $param = request()->param();
        try {
            validate([
                "page" => ["require", "number"],
                "pageSize" => ["require", "number"],
                "name" => ["chs"],
                "sex" => ["chs"],
                "stu_num" => ["number"]
            ], [
                "page.require" => "页码必须",
                "page.number" => "页码必须为数字！",
                "pageSize.require" => "pageSize必须",
                "pageSize.number" => "pageSize必须为数字！",
                "name.chs" => "姓名只能为中文！",
                "sex.chs"  => "性别只能为中文！",
                "stu_num.number" => "学号只能为数字"
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $studentListModel = new StudentListModel();
        $limit = intval($param['pageSize']);
        $page = intval($param['page']);
        if (isset($param['name'])){
            $name = Tools::remove_space($param['name']);
            $studentListModel = $studentListModel->whereLike('name', "%$name%");
        }
        if (isset($param['sex'])) {
            $sex = Tools::remove_space($param['sex']);
            $studentListModel = $studentListModel->whereLike('sex', "%$sex%");
        }
        if (isset($param['stu_num'])) {
            $stuNum = intval($param['stu_num']);
            $studentListModel = $studentListModel->where('stu_num', $stuNum);
        }
        try {
            $studentList = $studentListModel->paginate(['page' => $page, 'list_rows' => $limit]);
            $studentData = $studentList->toArray();
            $studentListInfo = $studentData['data'];
            unset($studentData['data']);
            $classIdArr = array_column($studentListInfo, 'class');
            $classIdArr = array_unique($classIdArr);
            $studentClassModel = new StudentClassModel();
            $classData = $studentClassModel->whereIn('id', $classIdArr)->select()->toArray();
            $collegeIdArr = array_column($classData, 'college_id');
            $collegeIdArr = array_unique($collegeIdArr);
            $studentCollegeModel = new StudentCollegeModel();
            $collegeData = $studentCollegeModel->whereIn('id', $collegeIdArr)->select()->toArray();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        try {
            $prefix = env("DATABASE_PREFIX") ?: config("database.prefix");
            $grades = Db::table($prefix . 'student_list')->field('grade')->group('grade')->select();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        $grade = [];
        foreach ($grades as $v) {
            $grade[] = $v['grade'];
        }
        $studentData = array_merge($studentData, [
            'data' => [
                'studentList' => $studentListInfo,
                'classList'   => $classData,
                'collegeList' => $collegeData,
            ]
        ]);
        $studentData['grades'] = $grade;
        return $this->actionSuccess($studentData);
    }

    /**
     * 导入学生数据
     * @return Response
     */
    public function importStudentData(): Response
    {
        $param = request()->param();
        if (!is_array($param) || empty($param)) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "数据有误！");
        }
        $paramLength = count($param);
        $studentClassModel = new StudentClassModel();
        $studentListModel = new StudentListModel();
        $studentCollegeModel = new StudentCollegeModel();
        try {
            $collegeList = $studentCollegeModel->select()->toArray();
            $classList = $studentClassModel->select()->toArray();
            $studentList = $studentListModel->select()->toArray();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        // 非首次导入要先处理一下数据
        if (!empty($collegeList)) {
            // 学院不为空
            foreach ($collegeList as $v) {
                $collegeList[$v['id']] = $v['name'];
            }
            unset($collegeList[0]);
            if (!empty($classList)) {
                // 班级不为空
                foreach ($classList as $v){
                    $classList[$v['id']] = $v['name'];
                }
                unset($classList[0]);
                if (!empty($studentList)) {
                    // 学生不为空
                    $classStudentList = [];
                    foreach ($studentList as $v) {
                        $classStudentList[$v['class']][] = [
                            'name' => $v['name'],
                            'sex'  => $v['sex'],
                            'stu_num' => $v['stu_num'],
                            'grade' => $v['grade'],
                        ];
                    }
                    unset($studentList);
                    $studentList = $classStudentList;
                }
            }

        }

        for ($i = 0; $i < $paramLength; $i++) {
            $collegeId = array_search($param[$i]['college'], $collegeList); // 学院存在就返回学院id，不存在为false
            if (!$collegeId) {
                $insert = $studentCollegeModel::create(['name' => $param[$i]['college']]);// 模型新增学院
                $collegeId = $insert->id; // 获取学院自增ID
            }
            $collegeId = intval($collegeId);
            for ($j = 0; $j < count($param[$i]['class']); $j++){
                $classId = array_search($param[$i]['class'][$j]['className'], $classList); // 班级存在就返回班级id，不存在为false
                if (!$classId) {
                    $insert = $studentClassModel::create(['name' => $param[$i]['class'][$j]['className'], 'college_id' => $collegeId]); // 模型新增班级
                    $classId = $insert->id; // 获取班级自增id
                }
                $classId = intval($classId);
                if (isset($studentList[$classId])) {
                    // 该班级存在学生数据考虑合并
                    $inStudent = Tools::array_diff_2($param[$i]['class'][$j]['studentList'], $studentList[$classId]);
                }else{
                    $inStudent = $param[$i]['class'][$j]['studentList'];
                }
                try {
                    $this->insertStudent($inStudent, $classId, $studentListModel);
                } catch (\Exception $e) {
                    return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
                }
            }
        }
        return $this->actionSuccess([], "导入成功！");
    }

    /**
     * 新增成功
     * @return Response
     */
    public function addStudent(): Response
    {
        $param = request()->param();
        try {
            validate(StudentDataManageValidate::class)->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        // 判断学院和班级关系
        try {
            $this->checkCollegeAndClass(intval($param['class']), intval($param['college']));
        } catch (Exception $e) {
            return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, $e->getMessage());
        }
        $add = StudentListModel::create([
            'name' => $param['name'],
            'class' => intval($param['class']),
            'stu_num' => $param['stu_num'],
            'sex' => $param['sex'],
            'grade' => $param['grade'],
        ]);
        if (!($add instanceof StudentListModel)) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, "新增失败！");
        }
        return $this->actionSuccess();
    }

    /**
     * 编辑学生
     * @return Response
     */
    public function editStudent(): Response
    {
        $param = request()->param();
        try {
            validate(StudentDataManageValidate::class)->scene('Edit')->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $classId = intval($param['class']);
        $collegeId = intval($param['college']);
        // 保证传入学院和班级一一对应
        try {
            $this->checkCollegeAndClass($classId, $collegeId);
        } catch (Exception $e) {
            return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, $e->getMessage());
        }
        $studentModel = new StudentListModel();
        try {
            $student = $studentModel->where('id', intval($param['id']))->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        $student->class = $classId; // 修改班级即可修改学院
        $student->name = $param['name'];
        $student->sex = $param['sex'];
        $student->stu_num = $param['stu_num'];
        $student->grade = intval($param['grade']);
        if (!$student->save()) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, "编辑失败！");
        }
        return $this->actionSuccess();
    }

    /**
     * 删除学生
     * @return Response
     */
    public function delStudent(): Response
    {
        $param = request()->param();
        if (empty($param)) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "参数不合法！");
        }
        try {
            validate([
                "ids" => ["array"],
                "grade" => ["number"],
            ], [
                "ids.array" => "要删除的选项非法！",
                "grade" => "年级只能为数字！"
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        if (isset($param['ids'])) {
            // 删除某些项
            $ids = $param['ids'];
            StudentListModel::destroy(function ($query) use($ids) {
               $query->whereIn("id", $ids);
            });
            return $this->actionSuccess();
        }elseif (isset($param['grade'])){
            // 删除整个年级
            $grade = intval($param['grade']);
            StudentListModel::destroy(function ($query) use($grade){
                $query->where("grade", $grade);
            });
            return $this->actionSuccess();
        }else{
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, "删除未知！");
        }
    }

    /**
     * 判断学院和班级关系
     * @throws Exception
     */
    private function checkCollegeAndClass(int $classId, int $collegeId)
    {
        $classModel = new StudentClassModel();
        try {
            $class = $classModel->where('id', $classId)->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            throw new Exception($e->getMessage());
        }
        // 判断班级
        if ($class == null) {
            throw new Exception("未找到该班级！");
        }
        // 判断学院
        if ($class->college_id != $collegeId) {
            throw new Exception("班级学院不一致！");
        }
    }

    /**
     * 插入学生列表
     * @param array $inStudent 待新增的学生数据 二维数组
     * @param int $classId 班级id
     * @param StudentListModel $studentListModel 学生列表表模型
     * @throws \Exception 异常处理
     */
    private function insertStudent(array $inStudent, int $classId, StudentList $studentListModel)
    {
        foreach ($inStudent as $k => $v) {
            $inStudent[$k]['class'] = $classId;
        }
        try {
            $studentListModel->saveAll($inStudent);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
