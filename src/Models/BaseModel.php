<?php

namespace Oyhdd\StatsCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Traits\AdminBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Encore\Admin\Auth\Database\Administrator;

class BaseModel extends Model
{
    use AdminBuilder;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $dates = [
        'create_time',
        'update_time',
    ];

    const CREATED_AT = "create_time";
    const UPDATED_AT = "update_time";

    /**
     * 数据状态
     */
    const STATUS_EFFECTIVE = 1;
    const STATUS_FORBID    = 2;
    const STATUS_DELETED   = -1;
    public static $label_status = [
        self::STATUS_EFFECTIVE => '有效',
        self::STATUS_FORBID    => '禁用',
        self::STATUS_DELETED   => '已删除',
    ];

    /**
     * 告警策略
     */
    const ALARM_ENABLE  = 1;
    const ALARM_DISABLE = 0;
    public static $label_enable_alarm = [
        self::ALARM_ENABLE  => '开启',
        self::ALARM_DISABLE => '关闭',
    ];

    /**
     * 告警方式
     */
    const ALARM_WECHAT = 1;
    const ALARM_MSG    = 2;
    const ALARM_EMAIL  = 3;
    public static $label_alarm_types = [
        self::ALARM_WECHAT => '微信',
        self::ALARM_MSG    => '短信',
        self::ALARM_EMAIL  => '邮件',
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'owner_uid', 'id');
    }

    public static function getUserList($uids = [])
    {
        return Administrator::where(function($query) use ($uids){
            if (!empty($uids)) {
                return $query->whereIn('id', $uids);
            }
        })->get()->pluck('name', 'id')->toArray();
    }

    public function setAlarmTypesAttribute($alarm_types) {
        $this->attributes['alarm_types'] = trim(implode($alarm_types, ','), ',');
    }

    public function setBackupUidsAttribute($backup_uids) {
        $this->attributes['backup_uids'] = trim(implode($backup_uids, ','), ',');
    }

    public function setAlarmUidsAttribute($alarm_uids) {
        $this->attributes['alarm_uids'] = trim(implode($alarm_uids, ','), ',');
    }

    public static function getList($ids=[])
    {
        return self::where(function($query) use ($ids){
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }
            return $query->where(['status' => self::STATUS_EFFECTIVE]);
        })->get();
    }

    /**
     * 使输入的代码安全
     * @param $string
     * @return string
     */
    public static function escape($string)
    {
        if (is_numeric($string))
        {
            return $string;
        }
        //HTML转义
        $string = htmlspecialchars($string, ENT_QUOTES, \Swoole::$charset);
        //启用了magic_quotes
        if (!get_magic_quotes_gpc())
        {
            $string = addslashes($string);
        }
        return $string;
    }
}