<?php
/**
 * Larfree Api类
 * @author xiao
 */
namespace App\Http\Controllers\Admin\Admin;

use Illuminate\Http\Request;
use Larfree\Controllers\AdminApisController as Controller;
use App\Models\Admin\AdminOperateRecord;
class OperateRecordController extends Controller
{
    public function __construct(AdminOperateRecord $model )
    {
        $this->model = $model;
        parent::__construct();
    }
}
