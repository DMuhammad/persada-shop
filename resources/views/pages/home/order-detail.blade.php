@extends('layouts.app')

@section('title', 'Detail Pesanan #' . $order->order_number)

@section('content')
    <div class="bg-white pt-36 pb-24">
        <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div>
                <a href="{{ route('order.index') }}"
                    class="text-sm font-medium text-persada-primary hover:text-persada-dark flex items-center gap-2 mb-4 transition-colors">
                    <x-heroicon-o-arrow-left class="h-4 w-4" />
                    Kembali ke Riwayat Pesanan
                </a>
                <div class="md:flex md:items-center md:justify-between">
                    <div class="min-w-0 flex-1">
                        <h1 class="text-3xl font-bold font-display tracking-tight text-gray-900 sm:text-4xl">Detail Pesanan
                        </h1>
                        <p class="mt-1 text-base text-gray-500">#{{ $order->order_number }} &bull; Dipesan pada
                            {{ $order->created_at->format('d F Y') }}</p>
                    </div>
                    <div class="mt-4 flex-shrink-0 flex md:mt-0 md:ml-4">
                        <a href="{{ route('order.invoice', $order) }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <x-heroicon-o-arrow-down-tray class="h-5 w-5 text-gray-500" />
                            Download Invoice
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-gray-50 rounded-2xl p-6">
                        <h3 class="text-base font-semibold text-gray-900">Status Pesanan</h3>
                        <div class="mt-2">
                            <span
                                class="px-3 py-1 text-xs font-bold rounded-full inline-flex items-center gap-1.5 {{ $order->status->badgeClass() }}">
                                @svg($order->status->icon(), 'h-3.5 w-3.5')
                                {{ $order->status->label() }}
                            </span>
                        </div>
                        @if ($order->status->awaitsPayment() && $order->payment_url)
                            <a href="{{ $order->payment_url }}" target="_blank"
                                class="mt-4 block w-full bg-persada-primary text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition hover:bg-persada-dark text-center">
                                Lanjutkan Pembayaran
                            </a>
                        @endif
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-6">
                        <h3 class="text-base font-semibold text-gray-900">Info Pengiriman</h3>
                        <div class="mt-2 text-sm text-gray-600 space-y-1">
                            <p class="font-bold text-gray-800">{{ $order->shipping_address['receiver_name'] ?? '-' }}</p>
                            <p>{{ $order->shipping_address['phone'] ?? '-' }}</p>
                            <p>{{ $order->shipping_address['street_address'] ?? '' }},
                                {{ $order->shipping_address['area_name'] ?? '' }}</p>
                            <p class="pt-1"><span class="font-medium">Kurir:</span> {{ $order->shipping_courier }}
                                ({{ $order->shipping_service }})</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-6">
                        <h3 class="text-base font-semibold text-gray-900">Total Pembayaran</h3>
                        <p class="mt-2 text-3xl font-bold text-persada-primary">
                            Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
                        <p class="text-sm text-gray-500">melalui
                            {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</p>
                    </div>
                </div>

                <div class="mt-10">
                    <h2 class="text-xl font-bold text-gray-900">Produk yang Dipesan</h2>
                    <div class="mt-4 flow-root">
                        <ul class="-my-6 divide-y divide-gray-200">
                            @foreach ($order->items as $item)
                                <li class="flex py-6">
                                    <div class="h-28 w-28 flex-shrink-0 overflow-hidden rounded-lg">
                                        <img src="{{ optional(optional(optional($item)->productVariant)->product)->primaryImage ? asset('storage/' . $item->productVariant->product->primaryImage->image_path) : asset('images/default-product.png') }}"
                                            alt="{{ $item->product_name ?? 'Produk Dihapus' }}"
                                            class="h-full w-full object-cover object-center bg-gray-100">
                                    </div>
                                    <div class="ml-4 flex flex-1 flex-col">
                                        <div>
                                            <div class="flex justify-between text-base font-medium text-gray-900">
                                                <h3>{{ $item->product_name }}</h3>
                                                <p class="ml-4">
                                                    Rp{{ number_format($item->price_at_purchase * $item->quantity, 0, ',', '.') }}
                                                </p>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-500">{{ $item->variant_name }}</p>
                                        </div>
                                        <div class="flex flex-1 items-end justify-between text-sm">
                                            <p class="text-gray-500">Qty: {{ $item->quantity }}</p>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
@endsection
