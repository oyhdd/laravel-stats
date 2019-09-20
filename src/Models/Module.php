<?php

namespace Oyhdd\StatsCenter\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 模块
 */
class Module extends BaseModel
{
    protected $table = "module";

    public function project() : BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public static function getList($moduleIds=[])
    {
        return self::where(function($query) use ($moduleIds){
            if (!empty($moduleIds)) {
                $query->whereIn('id', $moduleIds);
            }
            return $query->where(['status' => self::STATUS_EFFECTIVE]);
        })->get();
    }
}
