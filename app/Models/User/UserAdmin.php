<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */
namespace App\Models\User;
use Larfree\Models\Api;
use App\Scopes\User\UserAdminScope;
class UserAdmin extends Api
{
    use UserAdminScope;



  /*  public function roles()
    {
        return $this->belongsToMany('App\Models\Auth\AuthRole');
    }*/

}