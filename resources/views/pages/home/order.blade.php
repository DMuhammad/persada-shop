@extends('layouts.app')

@section('title', 'Pesanan Saya')

@section('content')
    <div class="bg-gray-50 pt-36 pb-24">
        <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="border-b border-gray-200 pb-8 mb-8">
                <h1 class="text-3xl font-bold font-display tracking-tight text-gray-900 sm:text-4xl">Riwayat Pesanan Saya
                </h1>
                <p class="mt-4 max-w-3xl text-base text-gray-500">Lacak dan lihat detail semua transaksi Anda di sini.</p>
            </div>

            <div class="mb-8">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                        <a href="{{ route('order.index') }}" @class([
                            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium',
                            'border-persada-primary text-persada-primary' => !$group,
                            'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $group,
                        ])>
                            Semua
                        </a>
                        @foreach ($groups as $statusGroup)
                            <a href="{{ route('order.index', ['status' => $statusGroup->value]) }}" @class([
                                'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium',
                                'border-persada-primary text-persada-primary' => $group === $statusGroup,
                                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' =>
                                    $group !== $statusGroup,
                            ])>
                                {{ $statusGroup->label() }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </div>

            <div class="space-y-8">
                @forelse ($orders as $order)
                    <section class="bg-white rounded-2xl shadow-sm">
                        <div class="p-4 bg-gray-50 rounded-t-2xl border-b border-gray-200">
                            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-500">Nomor Pesanan</dt>
                                    <dd class="mt-1 font-semibold text-gray-900">#{{ $order->order_number }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500">Tanggal</dt>
                                    <dd class="mt-1 font-medium text-gray-900">{{ $order->created_at->format('d M Y') }}
                                    </dd>
                                </div>
                                <div class="hidden sm:block">
                                    <dt class="font-medium text-gray-500">Total</dt>
                                    <dd class="mt-1 font-semibold text-gray-900">
                                        Rp{{ number_format($order->grand_total, 0, ',', '.') }}</dd>
                                </div>
                                <div class="hidden sm:block">
                                    <dt class="font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        <span
                                            class="px-2 py-0.5 text-xs font-bold rounded-full inline-flex items-center gap-1.5 {{ $order->status->badgeClass() }}">
                                            {{ $order->status->label() }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="p-6">
                            @if ($order->items->isNotEmpty())
                                @php $firstItem = $order->items->first(); @endphp
                                <div class="flex items-start gap-6">
                                    <img src="{{ optional(optional(optional($firstItem)->productVariant)->product)->primaryImage ? asset('storage/' . $firstItem->productVariant->product->primaryImage->image_path) : asset('images/default-product.png') }}"
                                        alt="{{ $firstItem->product_name ?? 'Produk Dihapus' }}"
                                        class="w-24 h-24 object-cover rounded-lg flex-shrink-0 bg-gray-100">
                                    <div class="flex-grow">
                                        <h4 class="font-bold text-lg text-gray-800">{{ $firstItem->product_name }}</h4>
                                        <p class="text-sm text-gray-500">{{ $firstItem->variant_name }}</p>
                                        @if ($order->items->count() > 1)
                                            <p class="text-sm text-gray-500 mt-1 italic">+{{ $order->items->count() - 1 }}
                                                produk lainnya</p>
                                        @endif
                                    </div>
                                    <div class="flex flex-col items-end gap-4">
                                        <a href="{{ route('order.detail', $order) }}"
                                            class="bg-persada-primary text-white font-semibold py-2 px-5 rounded-lg text-sm transition hover:bg-persada-dark">
                                            Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @empty
                    <div class="text-center py-20 border-2 border-dashed rounded-xl bg-white">
                        <x-heroicon-o-inbox class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">Tidak Ada Pesanan</h3>
                        <p class="mt-1 text-sm text-gray-500">Anda belum memiliki pesanan dengan status ini.</p>
                    </div>
                @endforelse
            </div>

            @if ($orders->hasPages())
                <div class="mt-8">
                    {{ $orders->links() }}
                </div>
            @endif
        </main>
    </div>
@endsection
