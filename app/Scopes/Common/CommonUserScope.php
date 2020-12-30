<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */
namespace App\Scopes\Common;
trait CommonUserScope
{
    public function scopeDriverSearch($model,$name){
        return $model->where(function ($query)use($name){
            $query->where('name','like','%'.$name.'%')
                ->orWhere('phone','like','%'.$name.'%');
        });
    }


    /**
     * 数据库权限使用
     * @param $query
     * @param $status
     * @return mixed
     */

    public function scopeAuthChannel($query, $status)
    {
        return $query->where("channel_id", $status);
    }
}