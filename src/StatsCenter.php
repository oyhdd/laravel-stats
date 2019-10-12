<?php

namespace Oyhdd\StatsCenter;

/**
 * 模调系统sdk
 *
 * 说明：模块id需要向模调系统申请
 *
 * 使用方法：若自行统计了请求耗时，则可只调用report
 * 1、tick()统计耗时     StatsCenter::tick($interface, $moduleId);
 * 2、report()上报数据   StatsCenter::report($interface, $moduleId, $success, $retCode, $serverIp);
 */
class StatsCenter
{
    const PACK_STATS  = 'NNCNNNN';
    const HOST_STATS  = '127.0.0.1:9903';
    const HOST_AOPNET = '127.0.0.1:9904';

    /**
     * tick时间数组
     */
    private static $timeMap = [];

    /**
     * @name   模块接口上报消耗时间记时
     * @uses   此函数只是统计请求耗时，若已统计了请求耗时，则可直接传入耗时到report函数进行上报
     * @param  string|int    $interface          接口名或接口id
     * @param  int           $moduleId           模块id
     * @return bool
     */
    public static function tick($interface, $moduleId)
    {
        try {
            if (!is_numeric($interface)) {
                $interface = self::getInterfaceId($interface, $moduleId);
            }
            self::$timeMap[$interface][$moduleId] = microtime(true);
        } catch (\Throwable $th) {
            return false;
        }
        return true;
    }

    /**
     * @name   上报统计数据
     * @param  string|int   $interface          接口名或接口id
     * @param  int          $moduleId           模块id
     * @param  bool         $success            请求是否成功
     * @param  int          $retCode            返回码
     * @param  string       $serverIp           服务端ip(非模调系统ip)
     * @param  int          $costTime           接口耗时(毫秒):若有值，则无需执行tick函数
     * @return bool
     */
    public static function report($interface, $moduleId, $success, $retCode, $serverIp = 0, $costTime = null)
    {
        try {
            if (!is_numeric($interface)) {
                $interface = self::getInterfaceId($interface, $moduleId);
            }

            //统计请求耗时
            if (is_null($costTime)) {
                if(isset(self::$timeMap[$interface][$moduleId]) && self::$timeMap[$interface][$moduleId] > 0) {
                    $timeStart = self::$timeMap[$interface][$moduleId];
                    self::$timeMap[$interface][$moduleId] = 0;
                } else {
                    $timeStart = microtime(true);
                }

                $costTime = (microtime(true) - $timeStart) * 1000;
            }

            $packData = pack(self::PACK_STATS, $interface, $moduleId, $success, $retCode, ip2long($serverIp), $costTime, time());
            return self::sendData('udp://'.self::HOST_STATS, $packData);
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * @name   自动获取接口id
     * @uses   首先获取本地缓存，如果没有则从服务器拉取
     * @param  string       $interface          接口名
     * @param  int          $moduleId           模块id
     * @return int
     */
    private static function getInterfaceId($interface, $moduleId)
    {
        $file = '/tmp/mostats/'.$moduleId.'_'.str_replace(['/', '\\', ' '], '_', $interface);
        if (!is_dir('/tmp/mostats')) {
            mkdir('/tmp/mostats', 0777);
            chmod('/tmp/mostats', 0777);
        }
        //若接口id为0，则5分钟内不请求模调系统
        if (is_file($file)) {
            $id = file_get_contents($file);
            if ($id != 0 || (time() - filemtime($file)) < 30) {
                return intval($id);
            }
        }
        try {
            $key = str_replace(' ', '_', $interface);
            $cli = stream_socket_client('tcp://' . self::HOST_AOPNET, $errno, $errstr, 1);
            stream_socket_sendto($cli, "GET {$moduleId} {$key}\r\n");
            $new_id = fread($cli, 1024);
            fclose($cli);
        } catch (\Throwable $th) {
            //log
        }

        if (empty($new_id)) {
            $new_id = 0;
        }

        file_put_contents($file, $new_id);
        chmod($file, 0777);
        return intval($new_id);
    }

    /**
     * 发送数据给统计系统
     * @param string    $address    模调系统地址
     * @param string    $packData   已打包的数据
     * @return bool
     */
    private static function sendData($address, $packData)
    {
        $socket = stream_socket_client($address);
        if (!$socket) {
            return false;
        }
        return stream_socket_sendto($socket, $packData) == strlen($packData);
    }
}
