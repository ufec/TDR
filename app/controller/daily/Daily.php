<?php
declare(strict_types=1);

namespace app\controller\daily;
use app\model\DailyList;
use app\util\ReturnCode;
use app\validate\Daily as DailyValidate;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Response;

class Daily extends Base
{
    /**
     * 新增日报
     * @return Response
     */
    public function addDaily(): Response
    {
        $param = request()->param();
        try {
            validate(DailyValidate::class)->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $data = $this->paramToData($param);
        if (isset($data['submit_time'])) {
            $data['submit_time'] = time() * 1000;
        }
        $create = DailyList::create($data);
        if (($create instanceof DailyList) && $create->id) {
            return $this->actionSuccess();
        }else{
            return $this->actionFailed(ReturnCode::DATABASE_ERROR);
        }
    }

    /**
     * 获取日报列表
     * @return Response
     */
    public function listDaily(): Response
    {
        $param = request()->param();
        try {
            validate([
                "id" => ["number"],
                "page" => ["require", "number"],
                "pageSize" => ["require", "number"],
            ], [
                "id.number" => "用户ID只能为数字！",
                "page.require" => "页码必须",
                "page.number" => "页码必须为数字！",
                "pageSize.require" => "pageSize必须",
                "pageSize.number" => "pageSize必须为数字！",
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        try {
            $dailyList = new DailyList();
            if (isset($param['id']) && $param['id'] == intval($param['id'])) {
                $dailyList = $dailyList->where('user_id', $param['id']);
            }
            $dailyList = $dailyList->paginate(['page'=>$param['page'], 'list_rows'=>$param['pageSize']]);
        } catch (DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        $data = [];
        $srcData = $dailyList->toArray();
        unset($srcData['data']);
        foreach ($dailyList as $item) {
            $item->teacher_name = $item->user->nick_name;
            unset($item->user);
            $data[] = $item;
        }
        $data = array_merge($srcData, ['data' => $data]);
        return $this->actionSuccess($data);
    }

    /**
     * 编辑日报
     * @return Response
     */
    public function editDaily(): Response
    {
        $param = request()->param();
        try {
            validate(DailyValidate::class)->scene('Edit')->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        try {
            $daily = (new DailyList())->where('id', intval($param['id']))->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if ($daily->user_id !== intval($param['teacherId'])) {
            return $this->actionFailed(ReturnCode::IDENTITY_NO_AUTH, "您没有权限操作此日报数据！");
        }
        if (!isset($param['update_time'])) {
            $param['update_time'] = time() * 1000;
        }
        $data = $this->paramToData($param);
        unset($data['submit_time']);
        if ($daily->save($data)){
            return $this->actionSuccess();
        }else{
            return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, "编辑失败！");
        }
    }

    /**
     * 删除日报
     * @return Response
     */
    public function delDaily(): Response
    {
        $param = request()->param();
        try {
            validate([
                "id" => ["require", "number"],
                "teacherId" => ["require", "number"]
            ], [
                "id.require" => "日报ID必须！",
                "id.number" => "日报ID非法！",
                "teacherId.require" => "用户ID必须！",
                "teacherId.number" => "用户ID非法！",
            ])->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        try {
            $daily = (new DailyList())->where('id', intval($param['id']))->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if ($daily->user_id !== intval($param['teacherId'])) {
            return $this->actionFailed(ReturnCode::IDENTITY_NO_AUTH, "您没有权限操作此日报数据！");
        }
        if ($daily->delete()) {
            return $this->actionSuccess();
        }else {
            return $this->actionFailed(ReturnCode::SYSTEM_EXEC_ERROR, "删除失败！");
        }
    }

    /**
     * 查询指定日报信息
     */
    public function dailyInfo(): Response
    {
        $id = request()->get('id');
        if (!is_numeric($id) || $id != intval($id)) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR);
        }
        try {
            $dailyInfo = (new DailyList())->where('id', $id)->find();
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        return $this->actionSuccess($dailyInfo->toArray());
    }

    /**
     * 将参数转为数据库字段数据
     * @param array $param
     * @return array
     */
    private function paramToData(array $param): array
    {
        $data = [];
        $data['user_id'] = $param['teacherId'];
        $data['teacher_name'] = $param['teacherName'];
        $data['department'] = $param['department'];
        $data['class_room']  = $param['classRoomName'];
        $data['post_date'] = $param['postDate'];
        $data['class'] = implode(",", $param['checkedClass']);
        $data['course'] = $param['course'];
        $data['course_nature'] = $param['courseNature'];
        $data['head_teacher'] = implode(",", $param['checkedHeadTeacher']);
        $data['section'] = $param['section'];
        $data['attend_num'] = $param['attendNum'];
        $data['true_attend_num'] = $param['trueAttendNum'];
        if (isset($param['checkedTruancyStudentList'])) {
            $data['truancy_student'] = implode(",", $param['checkedTruancyStudentList']);
            $data['truancy_student_num'] = $param['truancyStudentNum'];
        }
        if (isset($param['checkedLateStudentList'])) {
            $data['late_student'] = implode(",", $param['checkedLateStudentList']);
            $data['late_student_num'] = $param['lateStudentNum'];
        }
        if (isset($param['checkedLeaveEarlyStudentList'])) {
            $data['leave_early_student'] = implode(",", $param['checkedLeaveEarlyStudentList']);
            $data['leave_early_student_num'] = $param['leaveEarlyStudentNum'];
        }
        if (isset($param['checkedLeaveStudentList'])) {
            $data['leave_student'] = implode(",", $param['checkedLeaveStudentList']);
            $data['leave_student_num'] = $param['leaveStudentNum'];
        }
        if (isset($param['useMedia'])) {
            $data['use_media'] = $param['useMedia'];
        }
        if (isset($param['unImageStudentList'])) {
            $data['un_image_student'] = implode(",", $param['unImageStudentList']);
        }
        if (isset($param['projectorDamage'])) {
            $data['projector_damage'] = implode(",", $param['projectorDamage']);
        }
        if (isset($param['computerDamage'])) {
            $data['computer_damage'] = implode(",", $param['computerDamage']);
        }
        if (isset($param['otherWarnDamage'])) {
            $data['other_warn_damage'] = implode(",", $param['otherWarnDamage']);
        }
        if (isset($param['otherThings'])) {
            $data['other_things'] = $param['otherThings'];
        }
        if (isset($param['submit_time'])) {
            $data['submit_time'] = $param['submit_time'];
        }
        if (isset($param['update_time'])) {
            $data['update_time'] = $param['update_time'];
        }
        return $data;
    }
}