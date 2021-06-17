<?php


namespace app\controller\daily;
use app\model\TeacherConf as TeacherConfModel;
use app\model\StudentClass as StudentClassModel;
use app\util\ReturnCode;
use app\validate\TeacherConf as TeacherConfValidate;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Response;

class TeacherConf extends Base
{
    /**
     * 编辑 / 设置教师配置
     * @return Response
     */
    public function editTeacherConf(): Response
    {
        $param = request()->param();
        try {
            validate(TeacherConfValidate::class)->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }

        $teacherConfModel = new TeacherConfModel();
        try {
            $teacherConf = $teacherConfModel->where('user_id', intval($param['teacherId']))->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if (null === $teacherConf) {
            $teacherConf = TeacherConfModel::create([
                'user_id' => $param['teacherId'],
                'course_name' => implode(",", $param['course']),
                'class_id' => implode(",", $param['className']),
                'head_teacher' => implode(",", $param['headmaster']),
                'department' => $param['department'],
            ]);
            if ($teacherConf instanceof TeacherConfModel && $teacherConf->id && $teacherConf->user_id == $param['teacherId']) {
                return $this->actionSuccess();
            }else {
                return $this->actionFailed(ReturnCode::DATABASE_ERROR);
            }
        }else{
            $teacherConf->course_name = implode(",", $param['course']);
            $teacherConf->class_id = implode(",", $param['className']);
            $teacherConf->head_teacher = implode(",", $param['headmaster']);
            $teacherConf->department = $param['department'];
            if ($teacherConf->save()) {
                return $this->actionSuccess();
            }else{
                return $this->actionFailed(ReturnCode::DATABASE_ERROR);
            }
        }
    }

    /**
     * 获取教师配置
     * @return Response
     */
    public function getTeacherConf(): Response
    {
        $userId = request()->get('teacherId');
        if (!$userId || ($userId != intval($userId))) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR);
        }
        try {
            $userData = (new TeacherConfModel())->where('user_id', $userId)->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR);
        }
        if (null == $userData) {
            return $this->actionSuccess();
        }
        try {
            $studentClass = (new StudentClassModel())->whereIn('id', $userData->class_id)->field('name,id')->select();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        $classArr = [];
        foreach ($studentClass as $item){
            array_push($classArr, ['text' => $item->name, 'key' => $item->id]);
        }
        $data = $userData->toArray();
        unset($data['class_id']);
        $data['teacher_id'] = $data['user_id'];
        unset($data['user_id']);
        $data['class_name'] = $classArr;
        return $this->actionSuccess($data);
    }
}