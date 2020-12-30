<?php
/**
 * Larfree Api类
 * @author xiao
 */

namespace App\Http\Controllers\Api\Test;

use Illuminate\Http\Request;
use Larfree\Controllers\ApisController as Controller;
use App\Models\Test\TestTestDetail;

class TestDetailController extends Controller {
    public function __construct(TestTestDetail $model) {
        $this->model = $model;
        parent::__construct();
    }
}
