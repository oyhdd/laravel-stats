<?php

namespace Oyhdd\StatsCenter\Models;

class StatsClient extends BaseModel
{
    protected $table = "stats_client";

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
        'fail_server',
        'succ_server',
        'total_server',
        'ret_code',
        'succ_ret_code',
    ];

    public $timestamps=false;
}
