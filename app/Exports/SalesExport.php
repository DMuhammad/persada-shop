<?php

namespace App\Exports;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Str;

class SalesExport implements FromQuery, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;
    protected $status;
    protected $categoryId;

    public function __construct($startDate, $endDate, $status = null, $categoryId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
        $this->categoryId = $categoryId;
    }

    public function query()
    {
        $validStatus = OrderStatus::settledCases();

        return Order::query()
            ->with('user')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereIn('status', $validStatus)
            ->when($this->status, function ($q, $status) {
                return $q->where('status', $status);
            })
            ->when($this->categoryId, function ($q, $catId) {
                return $q->whereHas('items.productVariant.product', function ($subQuery) use ($catId) {
                    $subQuery->where('category_id', $catId);
                });
            })
            ->latest();
    }

    public function headings(): array
    {
        return [
            'Nomor Pesanan',
            'Nama Pelanggan',
            'Email Pelanggan',
            'Tanggal Pesanan',
            'Status',
            'Metode Pembayaran',
            'Subtotal',
            'Ongkos Kirim',
            'Grand Total',
        ];
    }

    public function map($order): array
    {
        $paymentMethodNames = [
            'bca_va'    => 'BCA VA',
            'bri_va'    => 'BRI VA',
            'bni_va'    => 'BNI VA',
            'echannel'  => 'Mandiri VA',
            'qris'      => 'QRIS',
            'gopay'     => 'GoPay',
            'shopeepay' => 'ShopeePay',
        ];

        $paymentMethod = $paymentMethodNames[$order->payment_method] ?? Str::title(str_replace('_', ' ', $order->payment_method));

        return [
            $order->order_number,
            $order->user->name ?? 'N/A',
            $order->user->email ?? 'N/A',
            Carbon::parse($order->created_at)->format('d F Y, H:i'),
            $order->status->label(),
            $paymentMethod ?? 'N/A',
            $order->subtotal,
            $order->shipping_cost,
            $order->grand_total,
        ];
    }
}
