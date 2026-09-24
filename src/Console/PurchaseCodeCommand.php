<?php

namespace Ashik\VersionUpdater\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseCodeCommand extends Command
{
    protected $signature = 'ashik:purchase-code
        {--customer= : Customer or company name}
        {--email= : Customer email}
        {--count=1 : Number of codes to generate}';

    protected $description = 'Generate customer purchase codes for Ashik installation';

    public function handle(): int
    {
        if (! DB::getSchemaBuilder()->hasTable('ashik_purchase_codes')) {
            $this->error('Run php artisan migrate first.');
            return self::FAILURE;
        }

        $count = max(1, min((int) $this->option('count'), 100));

        for ($i = 0; $i < $count; $i++) {
            do {
                $code = 'ASHIK-' . Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4));
            } while (DB::table('ashik_purchase_codes')->where('code', $code)->exists());

            DB::table('ashik_purchase_codes')->insert([
                'code' => $code,
                'customer_name' => $this->option('customer'),
                'customer_email' => $this->option('email'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->line($code);
        }

        return self::SUCCESS;
    }
}
