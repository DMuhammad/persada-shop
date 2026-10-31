@extends('layouts.admin')

@section('title', 'Detail Pesanan #' . $order->order_number)

@section('content')
    {{-- Header Halaman --}}
    <div>
        <a href="{{ route('dashboard.orders.index') }}"
            class="mb-4 inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
            @svg('heroicon-o-arrow-left', 'w-4 h-4 mr-2')
            Kembali ke Manajemen Pesanan
        </a>

        <div class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-x-3">
                    <h1 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:truncate sm:tracking-tight">
                        Pesanan #{{ $order->order_number }}
                    </h1>
                    <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $order->status->badgeClass() }}">
                        {{ $order->status->label() }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Dipesan pada {{ $order->created_at->translatedFormat('d F Y, H:i') }}
                </p>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0">
                <a href="{{ route('dashboard.orders.invoice', $order->order_number) }}"
                    class="inline-flex items-center gap-x-2 rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                    @svg('heroicon-o-arrow-down-tray', 'w-5 h-5 text-gray-500')
                    Cetak Invoice
                </a>
            </div>
        </div>
    </div>

    {{-- Layout Utama Konten --}}
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Kolom Kiri (Konten Utama) - Saya ubah posisi agar lebih baik --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Kartu Item Pesanan --}}
            <div class="p-6 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Ringkasan Pesanan</h3>
                <div class="flow-root">
                    <ul class="-my-4 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($order->items as $item)
                            <li class="flex items-center py-4 space-x-4">
                                <div class="flex-shrink-0">
                                    {{-- [INI BAGIAN PERBAIKANNYA] --}}
                                    @php
                                        // Ambil path gambar dari relasi yang sudah di-load di controller
                                        $imagePath = $item->productVariant->product->primaryImage->image_path ?? null;
                                    @endphp
                                    <div class="h-16 w-16 rounded-md overflow-hidden">
                                        @if ($imagePath)
                                            <img src="{{ asset('storage/' . $imagePath) }}" alt="{{ $item->product_name }}"
                                                class="h-full w-full object-cover object-center">
                                        @else
                                            {{-- Fallback jika tidak ada gambar --}}
                                            <div
                                                class="h-full w-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                @svg('heroicon-o-photo', 'w-8 h-8 text-gray-400')
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate dark:text-white">
                                        {{ $item->product_name }}</p>
                                    <p class="text-sm text-gray-500 truncate dark:text-gray-400">{{ $item->variant_name }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item->quantity }} x Rp
                                        {{ number_format($item->price_at_purchase, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div class="text-sm font-medium text-right text-gray-900 dark:text-white">
                                    Rp {{ number_format($item->price_at_purchase * $item->quantity, 0, ',', '.') }}
                                </div>
                            </li>
                        @empty
                            <li class="py-8 text-center text-gray-500">Tidak ada item dalam pesanan ini.</li>
                        @endforelse
                    </ul>
                </div>
                {{-- Total --}}
                <dl class="mt-6 space-y-3 text-sm border-t border-gray-200 pt-4 dark:border-gray-700">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">Rp
                            {{ number_format($order->subtotal, 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Ongkos Kirim</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">Rp
                            {{ number_format($order->shipping_cost, 0, ',', '.') }}</dd>
                    </div>
                    <div
                        class="flex justify-between text-base font-medium text-gray-900 dark:text-white border-t border-gray-200 pt-3 dark:border-gray-700">
                        <dt>Grand Total</dt>
                        <dd>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Kolom Kanan (Sidebar Info) --}}
        <div class="lg:col-span-1 flex flex-col gap-6">
            {{-- Kartu Ubah Status --}}
            <div class="p-6 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Ubah Status Pesanan</h3>
                <form action="{{ route('dashboard.orders.updateStatus', $order->order_number) }}" method="POST"
                    class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="status" class="sr-only">Status</label>
                        <select id="status" name="status"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-persada-primary focus:ring-persada-primary sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected($order->status === $status)>
                                    {{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-persada-primary hover:bg-persada-dark-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-persada-primary">
                        Simpan Perubahan
                    </button>
                </form>
            </div>

            {{-- Kartu Info Pelanggan & Alamat --}}
            <div class="p-6 bg-white rounded-lg shadow-sm dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <div class="pb-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Pelanggan</h3>
                    <div class="mt-3 text-sm space-y-1">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $order->user->name ?? 'N/A' }}</p>
                        <p class="text-gray-500 dark:text-gray-400">{{ $order->user->email ?? 'N/A' }}</p>
                        <p class="text-gray-500 dark:text-gray-400">{{ $order->user->phone ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="pt-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Alamat Pengiriman</h3>
                    <address class="mt-3 text-sm not-italic text-gray-500 dark:text-gray-400 space-y-1">
                        <p class="font-semibold text-gray-900 dark:text-white">
                            {{ $order->shipping_address['receiver_name'] }}</p>
                        <p>{{ $order->shipping_address['phone'] }}</p>
                        <p>{{ $order->shipping_address['street_address'] }}</p>
                        <p>{{ $order->shipping_address['area_name'] }}</p>
                    </address>
                </div>
            </div>
        </div>
    </div>
@endsection
