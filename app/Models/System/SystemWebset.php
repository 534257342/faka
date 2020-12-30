<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */
namespace App\Models\System;
use Larfree\Models\Api;
use App\Scopes\System\SystemWebsetScope;
class SystemWebset extends Api
{
    use SystemWebsetScope;
}