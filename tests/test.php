<?php
require dirname(__DIR__).'/src/StatsCenter.php';
use Oyhdd\StatsCenter\StatsCenter;
/**
 * 测试脚本
 * php test.php
 */
if (PHP_SAPI == 'cli') {
    $num = 1000;//测试数量
    $serverIps = ['192.168.39.1', '192.168.39.2', '192.168.39.3', '192.168.39.4'];
    $moduleIds = [100001, 100002, 100003, 100004];
    $interfaces = ['Test1','Test2','Test3','Test4'];
    for ($i=1; $i <= $num; $i++) {
        $interface = 'Test'.$interfaces[array_rand($interfaces, 1)];
        $serverIp = $serverIps[array_rand($serverIps, 1)];
        $moduleId = $moduleIds[array_rand($moduleIds, 1)];
        StatsCenter::tick($interface, $moduleId);
        $success = rand(0,1);
        $retCode = rand(0, 10);
        StatsCenter::report($interface, $moduleId, $success, $retCode, $serverIp, rand(0, 1));
    }
}