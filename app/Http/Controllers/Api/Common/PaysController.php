<?php
/**
 * Larfree Api类
 * @author blues
 */
namespace App\Http\Controllers\Api\Common;
use Illuminate\Http\Request;
use Larfree\Controllers\ApisController as Controller;
use App\Models\Common\CommonPays;
class PaysController extends Controller
{
    public function __construct(CommonPays $model)
    {
        $this->model = $model;
        parent::__construct();
    }
}