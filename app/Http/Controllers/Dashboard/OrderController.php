<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderStatusService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $currentStatus = is_numeric($request->query('status'))
            ? OrderStatus::tryFrom((int) $request->query('status'))
            : null;

        $orders = Order::latest()
            ->with('user')
            ->when($currentStatus, fn ($query) => $query->where('status', $currentStatus))
            ->paginate(10)
            ->withQueryString();

        return view('pages.dashboard.order', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
            'currentStatus' => $currentStatus,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.productVariant.product.primaryImage']);

        return view('pages.dashboard.order-detail', [
            'order' => $order,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function updateStatus(Request $request, Order $order, OrderStatusService $orderStatus)
    {
        $validated = $request->validate(
            ['status' => ['required', 'integer', Rule::in(OrderStatus::cases())]],
            ['status' => 'Status pesanan yang dipilih tidak valid.'],
        );

        $orderStatus->change($order, OrderStatus::from((int) $validated['status']));

        return redirect()->route('dashboard.orders.show', $order->order_number)->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function invoice(Order $order)
    {
        $order->load('items');

        $pdf = Pdf::loadView('pages.invoice', compact('order'));

        $fileName = 'invoice-' . $order->order_number . '.pdf';

        return $pdf->download($fileName);
    }
}
