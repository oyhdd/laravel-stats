<?php
require dirname(__DIR__).'/src/StatsCenter.php';
use Oyhdd\StatsCenter\StatsCenter;
/**
 * 测试脚本
 * php test.php -n1 -iTest -m100000
 */
if (PHP_SAPI == 'cli') {
    $options = getopt('n:i:m:', array());
    $num = $options['n'] ?? 1;//测试数量
    $interface = $options['i'] ?? 'Test';//接口名或接口id
    $moduleId = $options['m'] ?? 100000;//模块id

    for ($i=0; $i < $num; $i++) {
        // if ($i / 200 == 0) {
        //     sleep(1);
        // }
        $serverIps = ['192.168.39.1', '192.168.39.2', '192.168.39.3', '192.168.39.4'];
        $serverIp = $serverIps[array_rand($serverIps, 1)];
        StatsCenter::tick($interface, $moduleId);
        $success = rand(0,1);
        $retCode = rand(0, 10);
        StatsCenter::report($interface, $moduleId, $success, $retCode, $serverIp, rand(1000, 200000));
    }
}