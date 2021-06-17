<?php
declare (strict_types = 1);

namespace app\validate;

use think\Validate;

class Daily extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        "teacherId" => ["require", "number"], // 教师ID
        "teacherName" => ["require", "chs"], // 教师姓名
        "department" => ["require", "matchDepart:/[\x{4e00}-\x{9fa5}0-9a-zA-Z（）()-，,]+/u"], // 所属部门
        "classRoomName" => ["require", "number"], // 教室名
        "postDate" => ["require", "number"], // 上课时间
        "checkedClass" => ["require", "array"], // 班级列表
        "course" => ["require", "chsDash"], // 课程名称
        "courseNature" => ["require", "chs"], // 课程性质
        "checkedHeadTeacher" => ["require", "array"], // 班主任列表
        "section" => ["require", "alphaDash"], // 节次
        "attendNum" => ["require", "number"], // 应到人数
        "trueAttendNum" => ["require", "number"], // 实到人数

        "checkedTruancyStudentList" => ['array'], // 旷课学生列表
        "truancyStudentNum" => ['number'], // 旷课学生人数
        "checkedLateStudentList" => ['array'], // 迟到学生列表
        "lateStudentNum" => ['number'], // 迟到学生人数
        "checkedLeaveEarlyStudentList" => ['array'], // 早退学生列表
        "leaveEarlyStudentNum" => ['number'], // 早退学生人数
        "checkedLeaveStudentList" => ['array'], // 请假学生列表
        "leaveStudentNum" => ['number'], // 请假学生人数
        "useMedia" => ['chsDash'], // 使用手机或娱乐设备
        "unImageStudentList" => ['array'], // 学生不得体
        "projectorDamage" => ['array'], // 投影仪效果不好
        "computerDamage" => ['array'], // 电脑效果不好
        "otherWarnDamage" => ['array'], // 其他故障
        "otherThings" => ['chsDash'], // 其他情况备注
        "submit_time" => ['number'], // 提交时间
        "update_time" => ['number'], // 更新时间
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        "teacherId.require" => "未知用户！",
        "teacherId.number"  => "用户ID只能为数字！",
        "teacherName.require" => "教师姓名不得为空！",
        "department.require" => "未知部门，请先配置部门！",
        "department.matchDepart" => "部门数据非法！",
        "teacherName.chs" => "教师姓名只能为中文！",
        "classRoomName.require" => "教室编号必须！",
        "classRoomName.number" => "教室编号只能为数字！",
        "postDate.require" => "上课时间必须！",
        "postDate.number" => "上课时间只能为时间戳！",
        "checkedClass.require" => "班级还未选择！",
        "checkedClass.array" => "班级有误！",
        "course.require" => "课程名不能为空！",
        "course.chsDash" => "课程名只能为汉字、字母、数字和下划线_及破折号-",
        "courseNature.require" => "课程性质必选！",
        "courseNature.chs" => "课程性质只能为中文！",
        "checkedHeadTeacher.require" => "班主任还未选择！",
        "checkedHeadTeacher.array" => "班主任数据有误！",
        "section.require" => "上课节次不能为空！",
        "section.alphaDash" => "节次是能为数字或者-",
        "attendNum.require" => "应到人数不得为空！",
        "attendNum.number" => "应到人数只能为数字！",
        "trueAttendNum.require" => "实到人数不得为空！",
        "trueAttendNum.number" => "实到人数只能为数字！",

        "checkedTruancyStudentList.array" => "旷课学生数据有误！",
        "truancyStudentNum.number" => "旷课学生数目只能为数字！",
        "checkedLateStudentList.array" => "迟到学生数据有误！",
        "lateStudentNum.number" => "迟到学生数目只能为数字！",
        "checkedLeaveEarlyStudentList.array" => "早退学生数据有误！",
        "leaveEarlyStudentNum.number" => "早退学生人数只能为数字！",
        "checkedLeaveStudentList.array" => "请假学生数据有误！",
        "leaveStudentNum.number" => "请假学生人数只能为数字！",
        "useMedia.chsDash" => "使用媒体设备为汉字、字母、数字，下划线_及破折号-",
        "unImageStudentList.array" => "学生不得体数据有误！",
        "projectorDamage.array" => "投影仪问题数据有误！",
        "computerDamage.array" => "电脑问题数据有误！",
        "otherWarnDamage.array" => "其他问题数据有误！",
        "otherThings.chsDash" => "其他情况只能为汉字、字母、数字，下划线_及破折号-",
        "submit_time.number" => "提交时间只能是数字！",
        "update_time.number" => "更新时间只能是数字！",
        "id.require" => "日报ID必须！",
        "id.number" => "日报ID非法",
    ];

    // 自定义编辑验证场景
    public function sceneEdit(): Daily
    {
        return $this->append('id', 'require|number');
    }

    /**
     * 自定义正则校验
     * @param string $value
     * @param string $rule
     * @return bool
     */
    protected function matchDepart(string $value, string $rule): bool
    {
        preg_match($rule, $value, $res);
        return (bool)$res;
    }
}
