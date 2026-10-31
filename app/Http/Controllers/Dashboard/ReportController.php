<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Exports\SalesExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private function getDateRange(Request $request): array
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        return [$startDate, $endDate];
    }

    public function sales(Request $request)
    {
        $request->validate([
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'status'      => ['nullable', 'integer', Rule::in(OrderStatus::settledCases())],
            'category_id' => 'nullable|uuid|exists:categories,id',
        ]);

        [$startDate, $endDate] = $this->getDateRange($request);

        $categories = Category::orderBy('name')->get();
        $validStatus = OrderStatus::settledCases();

        $query = Order::whereIn('status', $validStatus)
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->when($request->category_id, fn($q, $catId) => $q->whereHas('items.productVariant.product', fn($sq) => $sq->where('category_id', $catId)));

        $currentQuery = (clone $query)->whereBetween('created_at', [$startDate, $endDate]);

        $totalRevenue = (clone $currentQuery)->sum('grand_total');
        $totalOrders = (clone $currentQuery)->count();
        $totalProductsSold = OrderItem::whereIn('order_id', (clone $currentQuery)->pluck('id'))->sum('quantity');
        $averageOrderValue = ($totalOrders > 0) ? $totalRevenue / $totalOrders : 0;
        $totalShipping = (clone $currentQuery)->sum('shipping_cost');

        $orders = (clone $currentQuery)->with('user')->withCount('items')->latest()->paginate(10)->withQueryString();

        $salesData = (clone $currentQuery)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get([
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(grand_total) as total')
            ])
            ->pluck('total', 'date');

        $dateRange = Carbon::parse($startDate)->toPeriod($endDate);
        $chartLabels = [];
        $chartData = [];
        foreach ($dateRange as $date) {
            $dateString = $date->format('Y-m-d');
            $chartLabels[] = $date->format('d M');
            $chartData[] = $salesData[$dateString] ?? 0;
        }

        $topProducts = OrderItem::query()
            ->whereIn('order_id', (clone $currentQuery)->pluck('id'))
            ->select('product_name', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->pluck('total_sold', 'product_name');

        $paymentMethodsData = (clone $currentQuery)
            ->select('payment_method', DB::raw('COUNT(id) as count'))
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method');

        $paymentMethodNames = [
            'bca_va'    => 'BCA VA',
            'bri_va'    => 'BRI VA',
            'bni_va'    => 'BNI VA',
            'echannel'  => 'Mandiri VA',
            'qris'      => 'QRIS',
            'gopay'     => 'GoPay',
            'shopeepay' => 'ShopeePay',
        ];

        $paymentMethods = $paymentMethodsData->mapWithKeys(function ($count, $key) use ($paymentMethodNames) {
            $readableName = $paymentMethodNames[$key] ?? Str::title(str_replace('_', ' ', $key));

            return [$readableName => $count];
        });

        return view('pages.dashboard.sales-report', compact(
            'orders',
            'startDate',
            'endDate',
            'categories',
            'totalRevenue',
            'totalOrders',
            'totalProductsSold',
            'averageOrderValue',
            'totalShipping',
            'chartLabels',
            'chartData',
            'topProducts',
            'paymentMethods'
        ));
    }

    public function customers(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        [$startDate, $endDate] = $this->getDateRange($request);

        $validStatus = OrderStatus::settledCases();

        $newCustomersCount = User::where('role', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $returningCustomersCount = Order::whereIn('status', $validStatus)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('user', function ($query) use ($startDate) {
                $query->where('created_at', '<', $startDate);
            })
            ->distinct('user_id')
            ->count('user_id');

        $customers = User::where('role', 'customer')
            ->whereHas('orders', function ($query) use ($startDate, $endDate, $validStatus) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->whereIn('status', $validStatus);
            })
            ->withSum(['orders' => function ($query) use ($startDate, $endDate, $validStatus) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->whereIn('status', $validStatus);
            }], 'grand_total')
            ->withCount(['orders' => function ($query) use ($startDate, $endDate, $validStatus) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->whereIn('status', $validStatus);
            }])
            ->orderByDesc('orders_sum_grand_total')
            ->paginate(10)
            ->withQueryString();

        return view('pages.dashboard.customer-report', compact(
            'customers',
            'startDate',
            'endDate',
            'newCustomersCount',
            'returningCustomersCount'
        ));
    }

    public function exportSales(Request $request)
    {
        $request->validate([
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'status'      => 'nullable|string',
            'category_id' => 'nullable|uuid',
        ]);

        [$startDate, $endDate] = $this->getDateRange($request);

        $fileName = 'laporan-penjualan-' . $startDate->format('d-m-Y') . '-sampai-' . $endDate->format('d-m-Y') . '.xlsx';

        return Excel::download(new SalesExport(
            $startDate,
            $endDate,
            $request->query('status'),
            $request->query('category_id')
        ), $fileName);
    }
}
