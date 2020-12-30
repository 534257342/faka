<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */
namespace App\Models\User;
use Larfree\Models\Api;
use App\Scopes\User\UserAuthScope;
class UserAuth extends Api
{
    use UserAuthScope;
}