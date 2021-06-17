<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;
use think\model\relation\HasOne;
use think\model\relation\HasMany;

/**
 * @mixin \think\Model
 */
class User extends Model
{
    public function role(): HasOne
    {
        return $this->hasOne(UserRole::class, "user_id", "id");
    }

    public function daily(): HasMany
    {
        return $this->hasMany(DailyList::class, "user_id", "id");
    }
}
