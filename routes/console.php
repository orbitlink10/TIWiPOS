<?php

use App\Models\SaleItem;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sales:backfill-unit-cost {--dry-run : Preview rows without updating}', function () {
    if (! Schema::hasColumn('sale_items', 'unit_cost')) {
        $this->error('Column sale_items.unit_cost not found. Run migrations first.');
        return self::FAILURE;
    }

    $query = SaleItem::query()->whereNull('unit_cost')->with('product:id,cost');
    $pending = (clone $query)->count();

    if ($pending === 0) {
        $this->info('No sale items need backfilling.');
        return self::SUCCESS;
    }

    $this->info("Sale items pending backfill: {$pending}");

    if ($this->option('dry-run')) {
        return self::SUCCESS;
    }

    $updated = 0;
    $skipped = 0;

    $query->orderBy('id')->chunkById(500, function ($items) use (&$updated, &$skipped) {
        foreach ($items as $item) {
            if (! $item->product) {
                $skipped++;
                continue;
            }

            $item->unit_cost = round((float) $item->product->cost, 2);
            $item->saveQuietly();
            $updated++;
        }
    });

    $this->info("Backfill complete. Updated: {$updated}. Skipped: {$skipped}.");

    return self::SUCCESS;
})->purpose('Backfill sale_items.unit_cost from current product costs for old sale rows');
