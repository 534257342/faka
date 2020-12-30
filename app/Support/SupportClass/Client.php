<?php
/**
 * Created by PhpStorm.
 * User: yons
 * Date: 2018/12/15
 * Time: 下午4:22
 */

namespace App\Support\SupportClass;

use Illuminate\Support\Facades\Log;

Class Client
{

    protected $data = '';
    protected $method = 'POST';
    protected $url = '';

    public function getClient()
    {
        return $client = new \GuzzleHttp\Client();
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }


    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setData($data)
    {

        $this->data = $data;
        return $data;
    }

    public function getDate()
    {
        return $this->data;
    }

    public function sendMessage()
    {
        $client = $this->getClient();
        try {
            $res = $client->request(
                $this->method,
                $this->url,
                ['verify' => false,
                    'json' => $this->getDate(),
                    'headers' => [
                        'Content-type' => 'application/json']
                ]
            );
        } catch (\Exception $e) {
            Log::debug('三方推送：' . $e->getMessage());
        }
        if (@$res) {
            $statusCode = $res->getStatusCode();
            if ($statusCode == 200) {
                $data = $res->getBody()->getContents();
                if (@$data) {
                    return $data;
                } else {
                    Log::debug('三方推送：对方服务器无信息返回');
                    return false;
                }
            } else {
                Log::debug('三方推送：对方服务器无法连接');
                return false;
            }
        }

    }

    public function sendKdnMessage()
    {
        $client = $this->getClient();
        try {
            $res = $client->request(
                $this->method,
                $this->url,
                ['verify' => false,
                    'body' => $this->getDate(),
                    'headers' => [
                        'Content-type' => 'application/json']
                ]
            );
        } catch (\Exception $e) {
            Log::debug('三方推送：' . $e->getMessage());
        }
        if (@$res) {
            $statusCode = $res->getStatusCode();
            if ($statusCode == 200) {
                $data = $res->getBody()->getContents();
                if (@$data) {
                    return $data;
                } else {
                    Log::debug('三方推送：对方服务器无信息返回');
                    return false;
                }
            } else {
                Log::debug('三方推送：对方服务器无法连接');
                return false;
            }
        }

    }
}