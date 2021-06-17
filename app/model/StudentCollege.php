<?php
declare (strict_types = 1);

namespace app\model;
use think\model\relation\HasMany;
use think\Model;

/**
 * @mixin \think\Model
 */
class StudentCollege extends Model
{
    /**
     * 一个学院有多个班级
     * @return HasMany
     */
    public function classInfo(): HasMany
    {
        return $this->hasMany(StudentClass::class, "college_id", "id");
    }
}
