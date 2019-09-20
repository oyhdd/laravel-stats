<?php

namespace Oyhdd\StatsCenter\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stats extends BaseModel
{
    protected $table = "stats";

    protected $fillable = [
        'interface_id',
        'module_id',
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
        'fail_client',
        'succ_client',
        'total_client',
        'ret_code',
        'cost_time',
        'succ_ret_code',
    ];

    public $timestamps=false;

    public function api() : BelongsTo
    {
        return $this->belongsTo(Api::class, 'interface_id', 'id');
    }

    public function module() : BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id', 'id');
    }
}
