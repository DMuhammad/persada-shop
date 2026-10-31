@extends('layouts.admin')

@section('content')
    <div class="space-y-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Selamat datang,
                {{ Auth::user()->name }}!</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Berikut adalah ringkasan performa toko Anda bulan ini.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <x-kpi-card metric="revenue" title="Omzet Bulan Ini" :value="$totalRevenue" format="currency" />
            <x-kpi-card metric="orders" title="Pesanan Bulan Ini" :value="$totalOrders" />
            <x-kpi-card metric="products" title="Pelanggan Baru Bulan Ini" :value="$newCustomersCount" />
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Grafik Penjualan (30 Hari Terakhir)</h3>
                <div class="mt-4 h-80"><canvas id="salesChart"></canvas></div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Produk Terlaris Bulan Ini</h3>
                <ul role="list" class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topProducts as $product)
                        <li class="flex py-3">
                            <div class="flex min-w-0 flex-1 items-center">
                                <div class="min-w-0 flex-1 px-4 md:grid md:grid-cols-2 md:gap-4">
                                    <div>
                                        <p class="truncate text-sm font-medium text-persada-primary">
                                            {{ $product->product_name }}</p>
                                    </div>
                                    <div class="hidden md:block">
                                        <div>
                                            <p class="text-sm text-gray-900 dark:text-gray-200">
                                                Terjual: <span class="font-semibold">{{ $product->total_sold }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="py-10 text-center text-sm text-gray-500">
                            Belum ada produk yang terjual bulan ini.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100">Pesanan Terbaru</h2>
            <div class="mt-4 overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/70">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">No.
                                Pesanan</th>
                            <th scope="col"
                                class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">
                                Pelanggan</th>
                            <th scope="col"
                                class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Total
                            </th>
                            <th scope="col"
                                class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse ($recentOrders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td
                                    class="whitespace-nowrap px-6 py-4 text-sm font-medium text-persada-primary hover:text-persada-dark-hover">
                                    <a href="#">#{{ $order->order_number }}</a>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                    {{ $order->user->name ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-200">Rp
                                    {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span
                                        class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20">{{ $order->status->label() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="py-20 text-center text-sm text-gray-500">
                                        Belum ada pesanan yang masuk.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if (session('auth_success'))
        <script type="module">
            window.addEventListener("DOMContentLoaded", () => {
                window.notyf.success(@json(session('auth_success')));
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDarkMode = document.documentElement.classList.contains('dark');
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            const labelColor = isDarkMode ? '#9ca3af' : '#6b7280';

            const salesCtx = document.getElementById('salesChart');
            if (salesCtx) {
                new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [{
                            label: 'Pendapatan',
                            data: @json($chartData),
                            fill: true,
                            backgroundColor: 'rgba(67, 179, 114, 0.2)',
                            borderColor: 'rgba(67, 179, 114, 1)',
                            tension: 0.3,
                            pointBackgroundColor: 'rgba(67, 179, 114, 1)',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: labelColor,
                                    callback: function(value) {
                                        if (value >= 1000000) return 'Rp ' + (value / 1000000) + ' Jt';
                                        if (value >= 1000) return 'Rp ' + (value / 1000) + ' Rb';
                                        return 'Rp ' + value;
                                    }
                                },
                                grid: {
                                    color: gridColor
                                }
                            },
                            x: {
                                ticks: {
                                    color: labelColor
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('id-ID', {
                                                style: 'currency',
                                                currency: 'IDR',
                                                minimumFractionDigits: 0
                                            }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
