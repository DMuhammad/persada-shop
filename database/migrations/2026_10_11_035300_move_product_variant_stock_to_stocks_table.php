<?php

use App\Enums\StockMovementType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Moves every variant balance into the stocks table as an opening balance movement.
     */
    public function up(): void
    {
        $now = now();

        DB::table('product_variants')->orderBy('id')->chunk(200, function ($variants) use ($now) {
            $stocks = [];
            $movements = [];

            foreach ($variants as $variant) {
                $stockId = (string) Str::uuid();

                $stocks[] = [
                    'id' => $stockId,
                    'product_variant_id' => $variant->id,
                    'quantity' => $variant->stock,
                    'reserved_quantity' => $variant->reserved_stock,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $movements[] = [
                    'id' => (string) Str::uuid(),
                    'stock_id' => $stockId,
                    'type' => StockMovementType::OPENING_BALANCE->value,
                    'quantity' => $variant->stock,
                    'balance_before' => 0,
                    'balance_after' => $variant->stock,
                    'note' => 'Saldo awal hasil migrasi dari data varian.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('stocks')->insert($stocks);
            DB::table('stock_movements')->insert($movements);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['stock', 'reserved_stock']);
        });
    }

    /**
     * Restores the balance columns on product variants from the stocks table.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->integer('stock')->default(0)->after('price');
            $table->unsignedInteger('reserved_stock')->default(0)->after('stock');
        });

        DB::table('stocks')->orderBy('id')->chunk(200, function ($stocks) {
            foreach ($stocks as $stock) {
                DB::table('product_variants')->where('id', $stock->product_variant_id)->update([
                    'stock' => $stock->quantity,
                    'reserved_stock' => $stock->reserved_quantity,
                ]);
            }
        });
    }
};
