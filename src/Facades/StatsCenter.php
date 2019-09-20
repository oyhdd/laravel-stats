<?php

namespace Oyhdd\StatsCenter\Facades;

use Illuminate\Support\Facades\Facade;

class StatsCenter extends Facade {

    /**
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'statscenter';
    }

}
