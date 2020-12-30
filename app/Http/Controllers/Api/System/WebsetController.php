<?php
/**
 * Larfree Api类
 * @author blues
 */
namespace App\Http\Controllers\Api\System;
use Illuminate\Http\Request;
use Larfree\Controllers\ApisController as Controller;
use App\Models\System\SystemWebset;
class WebsetController extends Controller
{
    public function __construct(SystemWebset $model)
    {
        $this->model = $model;
        parent::__construct();
    }
}