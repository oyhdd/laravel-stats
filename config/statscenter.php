<?php

if (config('app.env') == 'production') {
    $worker_num = 24;
    $daemonize  = 1;
} else {
    $worker_num = 1;
    $daemonize  = 0;
}

return [
    /************* swoole配置 *************/
    'swoole_setting' => [
        'udp' => [
            'worker_num'      => 8,  // 工作进程数量. 设置为CPU的1-4倍最合理
            'max_request'     => 1000, // 防止 PHP 内存溢出, 一个工作进程处理 X 次任务后自动重启 (注: 0,不自动重启)
            'daemonize'       => 0,  // 1后台运行

            'task_ipc_mode'    => 2,
            'dispatch_mode'    => 1,
            'task_worker_num'  => 10,  // 任务工作进程数量
            'task_max_request' => 1000, // 防止 PHP 内存溢出

            'open_length_check'     => true, // 启用后，可以保证Worker进程onReceive每次都会收到一个完整的数据包
            'package_length_type'   => 'N',
            'package_length_offset' => 0,
            'package_body_start'    => 4,
            'package_max_length'    => 8192,

            'pid_file'         => storage_path('logs/server.pid'),
            'worker_dump_file' => storage_path('logs/worker'),
            'task_dump_file'   => storage_path('logs/task'),

            'reload_async' => true,
        ],
        'tcp' => [
            'worker_num'      => 8,  // 工作进程数量. 设置为CPU的1-4倍最合理
            'max_request'     => 50, // 防止 PHP 内存溢出, 一个工作进程处理 X 次任务后自动重启 (注: 0,不自动重启)
            'daemonize'       => 0,  // 1后台运行
            'dispatch_mode'   => 3,

            'open_eof_check' => true,
            'open_eof_split' => true,
            'package_eof'    => "\r\n",
        ],
    ],

    /*********** 模调相关配置 *************/
    'save_day' => 90,// 数据保存多少天
    'time_key_min' => 5,// 统计时间间隔min
    'stats_port' => [
        'udp' => '9903',
        'tcp' => '9904',
    ],

    /************* 告警配置 *************/
    'alarm' => [
        // 企业微信配置
        'wechat' => [
            'class'       => 'Oyhdd\StatsCenter\Services\QyWechatServer', // 企业微信服务类
            'corp_secret' => '',// 企业秘钥
            'corp_id'     => '',// 企业id
            'agent_id'    => '',// AgentId
        ],
        // // 短信配置
        // 'sms' => [
        //     'class'       => 'Your Class Path', // 短信服务类
        //     'param1'      => '', // 参数
        //     'param2'      => '', // 参数
        // ],
        // // 邮件配置
        // 'email' => [
        //     'class'       => 'Your Class Path', // 邮件服务类
        //     'param1'      => '', // 参数
        //     'param2'      => '', // 参数
        // ]
    ]
];