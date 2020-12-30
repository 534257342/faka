<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */
namespace App\Scopes\Common;
trait CommonApplyScope
{

    /**
     * 限制查询只通过的信息。
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }

    /**
     * 获取只属于自己的信息。
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */

    public function scopeUser($query)
    {
        return $query->where('user_id', '=', getLoginUserID());
    }

}