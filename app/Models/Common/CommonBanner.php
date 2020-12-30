<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */

namespace App\Models\Common;

use Illuminate\Support\Facades\Log;
use Larfree\Models\Api;
use App\Scopes\Common\CommonBannerScope;


class CommonBanner extends Api {
    use CommonBannerScope;

    public function test2() {
        Log::debug(__METHOD__ . ' test');
        return true;
    }
}
