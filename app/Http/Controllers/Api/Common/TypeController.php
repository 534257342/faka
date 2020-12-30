<?php
/**
 * Larfree Api类
 * @author blues
 */
namespace App\Http\Controllers\Api\Common;
use Illuminate\Http\Request;
use Larfree\Controllers\ApisController as Controller;
use App\Models\Common\CommonType;
class TypeController extends Controller
{
    public function __construct(CommonType $model)
    {
        $this->model = $model;
        parent::__construct();
    }
}