<?php
declare (strict_types = 1);

namespace app\controller\system;
use app\util\ReturnCode;
use app\model\DailyList;
use app\validate\DailyDataManageValidate;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Response;

class DailyDataManage extends Base
{
    public function queryDailyInfo(): Response
    {
        $param = request()->param();
        try {
            validate(DailyDataManageValidate::class)->check($param);
        } catch (ValidateException $e) {
            return $this->actionFailed(ReturnCode::PARAM_VALIDATE_ERROR, $e->getMessage());
        }
        $dailyListModel = new DailyList();
        if (isset($param['teacherName'])) {
            $dailyListModel = $dailyListModel->whereLike("teacher_name", $param['teacherName']);
        }
        if (isset($param['startTime']) && isset($param['endTime'])) {
            $dailyListModel = $dailyListModel->whereBetween("submit_time", [$param['startTime'], $param['endTime']]);
        }
        if (isset($param['department'])) {
            $dailyListModel = $dailyListModel->whereLike("department", '%'.$param['department'].'%');
        }
        try {
            $dailyInfo =  $dailyListModel->order("submit_time", "desc")->paginate(["list_rows" => $param["pageSize"], "page" => $param['page']]);
        } catch (DataNotFoundException | ModelNotFoundException | DbException $e) {
            return $this->actionFailed(ReturnCode::DATABASE_ERROR, $e->getMessage());
        }
        if (null == $dailyInfo) {
            return $this->actionFailed(ReturnCode::EMPTY_DATA_ERROR, "空数据！");
        }
        return $this->actionSuccess($dailyInfo->toArray());
    }
}
