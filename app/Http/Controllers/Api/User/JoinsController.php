<?php
/**
 * Larfree Api类
 * @author blues
 */
namespace App\Http\Controllers\Api\User;
use Illuminate\Http\Request;
use Larfree\Controllers\ApisController as Controller;
use App\Models\User\UserJoins;
class JoinsController extends Controller
{
    public function __construct(UserJoins $model)
    {
        $this->model = $model;
        parent::__construct();
    }
}