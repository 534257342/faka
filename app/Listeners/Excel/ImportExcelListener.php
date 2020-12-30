<?php

namespace App\Listeners\Excel;

use App\Events\Excel\ImportExcel;
use App\Models\Common\CommonExcel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ImportExcelListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ImportExcel $event)
    {
        $Excel = new CommonExcel();

        $name = $event->name;
        $name = explode('.',$name);

        $name = $name[0];

        $site = $event->site;

        $data ='';

        switch ($name){

            case 'area':
                $data =$Excel->importArea($event->url,$site);
                break;
            case 'areas':
                $data =$Excel->importArea20($event->url,$site);
                break;
            case 'stations':
                $data =$Excel->importSpot20($event->url,$site);
                break;
            case 'user':
                break;
            case 'driver':
                $data = $Excel->importDriver($event->url,$site);
                break;
            case 'order':
                $data = $Excel->importOrder($event->url,$site);
                break;
            case 'orders':
                $data = $Excel->importUpdateOrder($event->url);
                break;
            case 'mainline':
                $data = $Excel->importMainLine($event->url);
                break;
            case 'channel':
                $data = $Excel->importChannel($event->url);
                break;
            case 'makeOrder':
                $data = $Excel->importMakeOrder($event->url);
                break;
        }
        return $data;
    }
}
