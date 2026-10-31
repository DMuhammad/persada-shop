<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderCancelledNotification;
use App\Services\Stock\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $statuses = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'];
        $currentStatus = $request->query('status');

        $orderQuery = Order::latest()->with('user');

        if (in_array($currentStatus, $statuses)) {
            $orderQuery->where('status', $currentStatus);
        }

        $orders = $orderQuery->paginate(10)->withQueryString();

        return view('pages.dashboard.order', [
            'orders' => $orders,
            'statuses' => $statuses,
            'currentStatus' => $currentStatus,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.productVariant.product.primaryImage']);

        $statuses = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'];

        return view('pages.dashboard.order-detail', [
            'order' => $order,
            'statuses' => $statuses
        ]);
    }

    public function updateStatus(Request $request, Order $order, StockService $stockService)
    {
        $statuses = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'refunded'];

        $request->validate([
            'status' => 'required|in:' . implode(',', $statuses),
        ]);

        $newStatus = $request->status;
        $oldStatus = $order->status;

        $order->update(['status' => $newStatus]);

        if ($newStatus !== $oldStatus && in_array($newStatus, ['cancelled', 'refunded'], true)) {
            $this->giveBackStock($order, $oldStatus, $stockService);
        }

        if ($newStatus === 'cancelled' && $oldStatus === 'pending_payment') {
            $admins = User::whereIn('role', ['admin', 'superadmin'])->get();
            Notification::send($admins, new OrderCancelledNotification($order));
        }

        return redirect()->route('dashboard.orders.show', $order->order_number)->with('success', 'Status pesanan berhasil diperbarui.');
    }

    /**
     * Gives the stock of a cancelled order back. An unpaid order only frees its
     * hold, while a paid one returns the goods as an incoming movement.
     */
    private function giveBackStock(Order $order, string $previousStatus, StockService $stockService): void
    {
        $paidStatuses = ['paid', 'processing', 'shipped', 'delivered', 'completed'];

        foreach ($order->items as $item) {
            if (! $item->productVariant) {
                continue;
            }

            if ($previousStatus === 'pending_payment') {
                $stockService->release($item->productVariant, $item->quantity);
            } elseif (in_array($previousStatus, $paidStatuses, true)) {
                $stockService->restock(
                    $item->productVariant,
                    $item->quantity,
                    $order,
                    "Pengembalian stok pesanan {$order->order_number}.",
                );
            }
        }
    }

    public function invoice(Order $order)
    {
        $order->load('items');

        $pdf = Pdf::loadView('pages.invoice', compact('order'));

        $fileName = 'invoice-' . $order->order_number . '.pdf';

        return $pdf->download($fileName);
    }
}
