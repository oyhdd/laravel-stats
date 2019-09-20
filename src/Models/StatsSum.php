<?php

namespace Oyhdd\StatsCenter\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatsSum extends BaseModel
{
    protected $table = "stats_sum";

    protected $fillable = [
        'date_key',
        'interface_id',
        'module_id',
        'interface_name',
        'module_name',
        'total_count',
        'fail_count',
        'succ_count',
        'total_time',
        'total_fail_time',
        'max_time',
        'min_time',
        'avg_time',
        'succ_rate',
        'avg_fail_time',
    ];

    public function api() : BelongsTo
    {
        return $this->belongsTo(Api::class, 'interface_id', 'id');
    }

    public function module() : BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id', 'id');
    }

    public $timestamps=false;

}
