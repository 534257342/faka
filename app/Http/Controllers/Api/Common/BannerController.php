<?php
/**
 * Larfree Api类
 * @author blues
 */

namespace App\Http\Controllers\Api\Common;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Larfree\Controllers\ApisController as Controller;
use App\Models\Common\CommonBanner;

class BannerController extends Controller {
    public function __construct(CommonBanner $model) {
        $this->model = $model;
        parent::__construct();
    }

    public function store(Request $request) {
        parent::store($request);
        $data = file_get_contents('php://input');
        Log::debug('参数获取:' . json_encode($data));
    }
}
