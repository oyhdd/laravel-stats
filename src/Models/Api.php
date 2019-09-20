<?php

namespace Oyhdd\StatsCenter\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 接口
 */
class Api extends BaseModel
{
    protected $table = "interface";

    public function module() : BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id', 'id');
    }

    /**
     * @name   获取接口id，没有则新建
     * @param  int         $module_id     模块id
     * @param  string      $interface_key 接口名称
     * @return int|false   id
     */
    public static function getInterfaceId($module_id, $interface_key)
    {
        // $interface_key = Api::escape($interface_key);
        $apiModel = Api::where(['module_id' => $module_id, 'name' => $interface_key])->first();
        if (empty($apiModel)) {
            $apiModel = new Api();
            $apiModel->name         = $interface_key;
            $apiModel->module_id     = $module_id;
            $apiModel->save();
        }

        return $apiModel->id ?? false;
    }

    public static function getListByModuleId($module_id)
    {
        return self::where(function($query) use ($module_id){
            if (!empty($module_id)) {
                $query->where(['module_id' => $module_id]);
            }
            return $query->where(['status' => self::STATUS_EFFECTIVE]);
        })->get();
    }
}
