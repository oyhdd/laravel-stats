<?php
require dirname(__DIR__).'/src/StatsCenter.php';
use Oyhdd\StatsCenter\StatsCenter;
/**
 * 测试脚本
 * php test.php
 */
if (PHP_SAPI == 'cli') {
    $data = [
        100000 => 'Test/Test1',
        100001 => 'Test/Test2',
        100002 => 'Test/Test3',
        100003 => 'Test/Test4',
    ];

    $num = 1000;//测试数量
    $serverIps = ['192.168.39.1', '192.168.39.2', '192.168.39.3', '192.168.39.4'];
    for ($i=1; $i <= $num; $i++) {
        $serverIp = $serverIps[array_rand($serverIps, 1)];
        $moduleId = array_rand($data, 1);
        $interface = $data[$moduleId];
        StatsCenter::tick($interface, $moduleId);
        if (rand(1, 10) > 2) {
            $success = 1;
            $retCode = 0;
            $cost_time = rand(0, 200);
        } else {
            $success = 0;
            $retCode = -1;
            $cost_time = rand(200, 1000);
        }
        StatsCenter::report($interface, $moduleId, $success, $retCode, $serverIp, $cost_time);
    }
}