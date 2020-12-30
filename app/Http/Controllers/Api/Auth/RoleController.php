<?php
/**
 * Larfree Api类
 * @author blues
 */
namespace App\Http\Controllers\Api\Auth;
use Illuminate\Http\Request;
use Larfree\Controllers\ApisController as Controller;
use App\Models\Auth\AuthRole;
class RoleController extends Controller
{
    public function __construct(AuthRole $model)
    {
        $this->model = $model;
        parent::__construct();
    }
}