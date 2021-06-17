<?php
declare (strict_types = 1);

namespace app\model;
use think\model\relation\HasOne;
use think\Model;

/**
 * @mixin \think\Model
 */
class DailyList extends Model
{
    public function user(): HasOne
    {
        return $this->hasOne(User::class, "id", "user_id");
    }
}
