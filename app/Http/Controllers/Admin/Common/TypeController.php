<?php
/**
 * Larfree Api类
 * @author xiao
 */
namespace App\Http\Controllers\Admin\Common;

use Illuminate\Http\Request;
use Larfree\Controllers\AdminApisController as Controller;
use App\Models\Common\CommonType;
class TypeController extends Controller
{
    public function __construct(CommonType $model )
    {
        $this->model = $model;
        parent::__construct();
    }
}