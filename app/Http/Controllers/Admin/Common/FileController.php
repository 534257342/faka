<?php
/**
 * Larfree Api类
 * @author xiao
 */
namespace App\Http\Controllers\Admin\Common;

use Illuminate\Http\Request;
use Larfree\Controllers\AdminApisController as Controller;
use App\Models\Common\CommonFile;
class FileController extends Controller
{
    public function __construct(CommonFile $model )
    {
        $this->model = $model;
        parent::__construct();
    }
}