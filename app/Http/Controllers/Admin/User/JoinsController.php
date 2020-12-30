<?php
/**
 * Larfree Api类
 * @author blues
 */
namespace App\Http\Controllers\Admin\User;

use Illuminate\Http\Request;
use Larfree\Controllers\AdminApisController as Controller;
use App\Models\User\UserJoins;
class JoinsController extends Controller
{
    public function __construct(UserJoins $model )
    {
        $this->model = $model;
        parent::__construct();
    }
}