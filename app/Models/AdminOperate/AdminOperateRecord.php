<?php
/**
 * 基础的apiModel类
 * @author xiao
 */

namespace App\Models\AdminOperate;

use Larfree\Models\Api;
use App\Scopes\AdminOperate\AdminOperateRecordScope;

class AdminOperateRecord extends Api {
    use AdminOperateRecordScope;
}
