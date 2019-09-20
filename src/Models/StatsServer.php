<?php

namespace Oyhdd\StatsCenter\Models;

class StatsServer extends BaseModel
{
    protected $table = "stats_server";

    protected $fillable = [
        'interface_id',
        'module_id',
        'ip',
        'time_key',
        'date_key',
        'total_count',
        'fail_count',
        'total_time',
        'total_fail_time',
        'avg_time',
        'avg_fail_time',
        'max_time',
        'min_time',
        'fail_client',
        'succ_client',
        'total_client',
        'ret_code',
        'succ_ret_code',
    ];

    public $timestamps=false;
}
