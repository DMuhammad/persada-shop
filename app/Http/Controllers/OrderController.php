<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatusGroup;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $group = is_numeric($request->query('status'))
            ? OrderStatusGroup::tryFrom((int) $request->query('status'))
            : null;

        $orders = Auth::user()->orders()
            ->when($group, fn ($query) => $query->whereIn('status', $group->statuses()))
            ->with('items.productVariant.product.primaryImage')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.home.order', [
            'orders' => $orders,
            'groups' => OrderStatusGroup::cases(),
            'group' => $group,
        ]);
    }

    public function orderDetail(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(404);
        }

        return view('pages.home.order-detail', compact('order'));
    }

    public function invoice(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(404);
        }

        $pdf = Pdf::loadView('pages.invoice', compact('order'));

        $fileName = 'invoice-' . $order->order_number . '.pdf';

        return $pdf->download($fileName);
    }
}
