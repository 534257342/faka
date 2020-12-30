<?php
/**
 * Larfree Api类
 * @author xiao
 */

namespace App\Http\Controllers\Api\Test;

use App\Events\ExpressMission\MissionArrived;
use App\Events\ExpressOrder\OrderCompleted;
use App\Listeners\MainVrp\Arrived;
use App\Models\Common\CommonBanner;
use App\Models\Express\ExpressMission;
use App\Models\Express\ExpressOrder;
use App\Support\Facades\Queue;
use App\Support\QueueService;
use Illuminate\Http\Request;
use Larfree\Controllers\ApisController as Controller;
use  \App\Support\Traits\MyRedis;

class TestController extends Controller {
    use  MyRedis;

    public function __construct(CommonBanner $model) {
        $this->model = $model;
        parent::__construct();
    }

    public function test(Request $request) {
        Queue::add('guard', [
            'is_model' => 1,
            'order'    => ['id' => 12313],
            'callback' => ['App\Models\Common\CommonBanner', 'test2']
        ], time() + 10);
    }

    public function test2() {

    }
}
