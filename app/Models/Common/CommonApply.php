<?php

/**
 * Larfree Api类
 * @author xiao
 */
namespace App\Models\Common;



use App\Scopes\Common\CommonApplyScope;
use Larfree\Models\Api;


class CommonApply extends Api
{

    use CommonApplyScope;
    public function type()
    {
        return $this->hasOne(CommonType::class,'id','type_id');

    }

    public function user()
    {
        return $this->hasOne(CommonUser::class,'id','user_id');

    }




}
