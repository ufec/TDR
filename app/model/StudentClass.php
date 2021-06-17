<?php
declare (strict_types = 1);

namespace app\model;
use think\model\relation\HasMany;
use think\model\relation\BelongsTo;
use think\Model;

/**
 * @mixin \think\Model
 */
class StudentClass extends Model
{
    /**
     * 一个班级有多个学生，一对多
     * @return HasMany
     */
    public function student(): HasMany
    {
        return $this->hasMany(StudentList::class, "class", "id");
    }

    /**
     * 一个班级属于一个学院
     * @return BelongsTo
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(StudentCollege::class, "id", "college_id");
    }
}
