<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Http\Controllers\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class inventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:status {query} {show} {file?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inventory status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $query = $this->argument('query');
        if (!Helpers::validateDate($query, 'Y-m-d')) {
            throw new \Exception('Inventory date is invalid');
        }

        $show = $this->argument('show');
        if (!Helpers::validateDate($show, 'Y-m-d')) {
            throw new \Exception('Show date is invalid');
        }

        $file = $this->argument('file');
        if (!$file) {
            $file = getenv('DEFAULT_CSV_FILE');
        }
        $file = storage_path('app/public/').$file;

        $inventory = new InventoryService();
        $inventory->loadFile($file);
        $data = $inventory->find($query, $show);

        $queryResult = json_encode($data->groupBy(
            'genre',
            'title',
            'tickets left',
            'tickets available',
            'status'
        ));

        dd($queryResult, $query, $show, $file);
    }
}
