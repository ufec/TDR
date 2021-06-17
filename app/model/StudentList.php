<?php
declare (strict_types = 1);

namespace app\model;
use think\model\relation\BelongsTo;
use think\model\relation\HasOne;
use think\Model;

/**
 * @mixin \think\Model
 */
class StudentList extends Model
{
    /**
     * 一个学生只有一个班级，一对一
     * @return HasOne
     */
    public function classInfo(): HasOne
    {
        return $this->hasOne(StudentClass::class, "id", "class");
    }
}
