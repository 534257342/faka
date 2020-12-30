<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */
namespace App\Models\Common;
use Larfree\Models\Api;
use App\Scopes\Common\CommonPaysScope;
class CommonPays extends Api
{
    use CommonPaysScope;
}