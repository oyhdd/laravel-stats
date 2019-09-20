<?php

namespace Oyhdd\StatsCenter\Models;

/**
 * 项目
 */
class Project extends BaseModel
{
    protected $table = "project";

    public static function getList($projectIds=[])
    {
        return self::where(function($query) use ($projectIds){
            if (!empty($projectIds)) {
                $query->whereIn('id', $projectIds);
            }
            return $query->where(['status' => self::STATUS_EFFECTIVE]);
        })->get();
    }
}
