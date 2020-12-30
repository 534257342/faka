<?php

namespace App\Models\Admin;

use App\Scopes\Admin\AdminNavScope;
use Larfree\Models\Admin\AdminNav as Nav;
use DB;//载入DB类
class AdminNav extends Nav {
    use AdminNavScope;

    public function findParentID($id) {
        $data = self::query()->where('id', $id)->select('parent_id')->first();
        if ($data->parent_id != 0) {
            return $data->parent_id;
        }
    }

}
