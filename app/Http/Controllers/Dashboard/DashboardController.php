<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfDay();
        $validStatus = OrderStatus::settledCases();

        $totalRevenue = Order::whereIn('status', $validStatus)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('grand_total');

        $totalOrders = Order::whereIn('status', $validStatus)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $newCustomersCount = User::where('role', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $salesStartDate = Carbon::now()->subDays(29)->startOfDay();
        $salesEndDate = Carbon::now()->endOfDay();

        $salesData = Order::whereIn('status', $validStatus)
            ->whereBetween('created_at', [$salesStartDate, $salesEndDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get([
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(grand_total) as total')
            ])
            ->pluck('total', 'date');

        $dateRange = Carbon::parse($salesStartDate)->toPeriod($salesEndDate);
        $chartLabels = [];
        $chartData = [];
        foreach ($dateRange as $date) {
            $dateString = $date->format('Y-m-d');
            $chartLabels[] = $date->format('d M');
            $chartData[] = $salesData[$dateString] ?? 0;
        }

        $recentOrders = Order::with('user')->latest()->take(5)->get();

        $topProducts = OrderItem::query()
            ->whereHas('order', function ($query) use ($startDate, $endDate, $validStatus) {
                $query->whereIn('status', $validStatus)
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->select('product_name', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalRevenue',
            'totalOrders',
            'newCustomersCount',
            'chartLabels',
            'chartData',
            'recentOrders',
            'topProducts'
        ));
    }
}
