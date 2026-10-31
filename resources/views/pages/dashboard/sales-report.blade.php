@extends('layouts.admin')

@section('title', 'Laporan Penjualan')

@section('content')
    <div class="space-y-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Laporan Penjualan</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Analisis performa penjualan dalam rentang waktu tertentu.
            </p>
        </div>

        <div class="border-b border-gray-200 pb-5 dark:border-gray-700">
            <form method="GET" action="{{ route('reports.sales') }}" class="flex flex-col gap-4 md:flex-row md:items-end">
                <div class="grid flex-grow grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal
                            Mulai</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-persada-primary focus:ring-persada-primary sm:text-sm dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-persada-primary">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal
                            Selesai</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-persada-primary focus:ring-persada-primary sm:text-sm dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-persada-primary">
                    </div>
                    <div>
                        <label for="status"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status" id="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-persada-primary focus:ring-persada-primary sm:text-sm dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-persada-primary">
                            <option value="">Semua Status</option>
                            @foreach (App\Enums\OrderStatus::settledCases() as $status)
                                <option value="{{ $status->value }}" @selected(request('status') == $status->value)>
                                    {{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="category_id"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori</label>
                        <select name="category_id" id="category_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-persada-primary focus:ring-persada-primary sm:text-sm dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-persada-primary">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-shrink-0 items-center gap-2 pt-4 md:pt-0">
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-persada-primary px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-persada-dark-hover focus:outline-none focus:ring-2 focus:ring-persada-primary focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:w-auto">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('reports.sales.export', request()->all()) }}"
                        class="inline-flex w-full items-center justify-center gap-x-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 sm:w-auto">
                        @svg('heroicon-o-arrow-down-tray', 'w-4 h-4')
                        Export
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <x-kpi-card metric="revenue" title="Total Omzet" :value="$totalRevenue" format="currency" />
            <x-kpi-card metric="orders" title="Jumlah Pesanan" :value="$totalOrders" />
            <x-kpi-card metric="products" title="Produk Terjual" :value="$totalProductsSold" />
            <x-kpi-card metric="aov" title="Rata-rata/Pesanan" :value="$averageOrderValue" format="currency" />
            <x-kpi-card metric="shipping" title="Total Ongkir" :value="$totalShipping" format="currency" />
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Grafik Tren Penjualan</h3>
                <div class="mt-4 h-80"><canvas id="salesChart"></canvas></div>
            </div>
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Metode Pembayaran</h3>
                <div class="mt-4 h-80"><canvas id="paymentMethodsChart"></canvas></div>
            </div>
        </div>
        <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">5 Produk Terlaris</h3>
            <div class="mt-4 h-80"><canvas id="topProductsChart"></canvas></div>
        </div>

        <div>
            <h2 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100">Rincian Pesanan</h2>
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
                                class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Tanggal
                            </th>
                            <th scope="col"
                                class="px-6 py-3.5 text-center text-sm font-semibold text-gray-900 dark:text-gray-200">
                                Jumlah Item</th>
                            <th scope="col"
                                class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Total
                            </th>
                            <th scope="col"
                                class="px-6 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td
                                    class="whitespace-nowrap px-6 py-4 text-sm font-medium text-persada-primary hover:text-persada-dark-hover">
                                    <a href="#">#{{ $order->order_number }}</a>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                    {{ $order->user->name ?? 'N/A' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $order->created_at->translatedFormat('d M Y, H:i') }}</td>
                                <td
                                    class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ $order->items_count }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-200">Rp
                                    {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span
                                        class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20">{{ $order->status->label() }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="py-20 text-center">
                                        @svg('heroicon-o-document-chart-bar', 'mx-auto h-12 w-12 text-gray-400')
                                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-200">Tidak ada
                                            data penjualan</h3>
                                        <p class="mt-1 text-sm text-gray-500">Tidak ada data yang ditemukan untuk rentang
                                            tanggal yang dipilih.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="mt-5">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
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

            const paymentCtx = document.getElementById('paymentMethodsChart');
            if (paymentCtx) {
                const paymentData = @json($paymentMethods);
                new Chart(paymentCtx, {
                    type: 'pie',
                    data: {
                        labels: Object.keys(paymentData),
                        datasets: [{
                            data: Object.values(paymentData),
                            backgroundColor: ['#4ade80', '#fbbf24', '#60a5fa', '#f87171',
                                '#c084fc'
                            ],
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: labelColor
                                }
                            }
                        }
                    }
                });
            }

            const topProductsCtx = document.getElementById('topProductsChart');
            if (topProductsCtx) {
                const topProductsData = @json($topProducts);
                new Chart(topProductsCtx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(topProductsData),
                        datasets: [{
                            label: 'Jumlah Terjual',
                            data: Object.values(topProductsData),
                            backgroundColor: 'rgba(67, 179, 114, 0.5)',
                            borderColor: 'rgba(67, 179, 114, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    color: labelColor
                                },
                                grid: {
                                    color: gridColor
                                }
                            },
                            y: {
                                ticks: {
                                    color: labelColor
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
