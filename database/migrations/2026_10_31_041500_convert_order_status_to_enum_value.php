<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the status names on orders with their enum values.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('status_value')
                ->default(OrderStatus::PENDING_PAYMENT->value)
                ->after('status');
        });

        foreach ($this->names() as $name => $status) {
            DB::table('orders')->where('status', $name)->update(['status_value' => $status->value]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('status_value', 'status');
        });
    }

    /**
     * Puts the status names back on orders.
     */
    public function down(): void
    {
        $names = $this->names();

        Schema::table('orders', function (Blueprint $table) use ($names) {
            $table->enum('status_name', array_keys($names))
                ->default(array_key_first($names))
                ->after('status');
        });

        foreach ($names as $name => $status) {
            DB::table('orders')->where('status', $status->value)->update(['status_name' => $name]);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('status_name', 'status');
        });
    }

    /**
     * Returns the status name every enum value was stored as before.
     *
     * @return array<string, OrderStatus>
     */
    private function names(): array
    {
        return [
            'pending_payment' => OrderStatus::PENDING_PAYMENT,
            'paid' => OrderStatus::PAID,
            'processing' => OrderStatus::PROCESSING,
            'shipped' => OrderStatus::SHIPPED,
            'delivered' => OrderStatus::DELIVERED,
            'completed' => OrderStatus::COMPLETED,
            'cancelled' => OrderStatus::CANCELLED,
            'refunded' => OrderStatus::REFUNDED,
        ];
    }
};
