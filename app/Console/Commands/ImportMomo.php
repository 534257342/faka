<?php

namespace App\Console\Commands;

use App\Support\Facades\Elastic;
use Illuminate\Console\Command;

class ImportMomo extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:momo {name} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $name = $this->argument('name');
        set_time_limit(0);
        ini_set('memory_limit', '3072M');
        $path = "app/public/" . $name;
        $file = (storage_path($path));
        if (is_file($file)) {
            $arr = file($file);
            foreach ($arr as $k => $item) {
                $data = explode("----", str_replace(array("\n", "\r"), "", $item));
                Elastic::index('momo')->addOne(['_id' => md5($data[0]), 'account' => $data[0] ?? '', 'password' => $data[1] ?? '', 'from' => 'momo']);
                print_r($item);
            }
        }
    }
}
