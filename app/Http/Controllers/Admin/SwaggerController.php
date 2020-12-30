<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Larfree\Controllers\SwaggerController as Controller;


class SwaggerController extends Controller
{


    protected $path;
    /**
     *  返回JSON格式的Swagger定义
     *  @OA\Info(
     *     title="My First API",
     *     version="0.1"
     *  )
     *
     *  定义根路径,可多个
     *  @OA\Server(url="/api/admin/")
     *
     *  定义认证方式
     *  @OA\SecurityScheme(
     *   securityScheme="jwt",
     *   type="apiKey",
     *   in="header",
     *   name="Authorization"
     * )
     */
    public function __construct()
    {
        $this->path =  dirname(__FILE__);
    }



}
