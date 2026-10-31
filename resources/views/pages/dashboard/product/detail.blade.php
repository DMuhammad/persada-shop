@extends('layouts.admin')

@section('title', 'Detail Produk')

@section('content')
    <div x-data>
        <div class="flex flex-col items-start justify-between gap-4 mb-6 sm:flex-row sm:items-center">
            <div>
                <a href="{{ route('master.products.index') }}"
                    class="mb-2 inline-flex items-center text-sm font-medium text-gray-600 hover:text-persada-primary dark:text-gray-400 dark:hover:text-persada-accent">
                    <x-heroicon-o-arrow-left class="w-4 h-4 mr-2" />
                    Kembali ke Manajemen Produk
                </a>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $product->name }}
                    </h1>
                    <span
                        class="inline-flex items-center gap-x-1.5 px-2.5 py-1 text-xs font-medium rounded-full {{ $product->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                        @if ($product->status === 'active')
                            <x-heroicon-s-check class="w-2 h-2 fill-green-500" />
                        @else
                            <x-heroicon-s-x-mark class="w-2 h-2 fill-gray-500" />
                        @endif
                        {{ ucfirst($product->status) }}
                    </span>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900/30">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-x-circle class="h-5 w-5 text-red-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-300">Terdapat {{ $errors->count() }}
                            error pada input Anda:</h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-400">
                            <ul role="list" class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="space-y-8">
            <div class="p-6 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Detail Produk</h2>
                <dl class="space-y-4">
                    <div class="flex items-start gap-12 sm:gap-16">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategori</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $product->category->name ?? '-' }}</dd>
                        </div>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Deskripsi</dt>
                        <dd class="mt-2 prose prose-sm max-w-none dark:prose-invert text-gray-600 dark:text-gray-300">
                            {!! $product->description !!}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="p-6 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Gambar Produk</h2>
                    <button type="button" @click="$dispatch('open-product-image')"
                        class="inline-flex items-center rounded-md bg-persada-primary px-3 py-2 text-sm font-semibold text-white hover:bg-persada-dark-hover">
                        <x-heroicon-o-arrow-up-tray class="w-4 h-4 sm:-ml-1 sm:mr-2" />
                        <span class="hidden sm:inline">Tambah Gambar</span>
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6">
                    @forelse ($product->images as $image)
                        <div class="relative group aspect-square">
                            <img src="{{ asset('storage/' . $image->image_path) }}" alt="Gambar produk"
                                class="object-cover w-full h-full rounded-md shadow">
                            <div
                                class="absolute inset-0 flex items-center justify-center transition-opacity bg-black rounded-md opacity-0 bg-opacity-40 group-hover:opacity-100">
                                <form action="{{ route('master.products.images.destroy', [$product, $image]) }}"
                                    method="POST">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="confirmDelete(this.closest('form'))"
                                        class="p-2 text-white bg-red-600 rounded-full hover:bg-red-700">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </form>
                            </div>
                            @if ($image->is_primary)
                                <span
                                    class="absolute px-2 py-0.5 text-xs font-medium text-white bg-green-600 rounded-full top-2 left-2">Utama</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 col-span-full">Belum ada gambar produk.</p>
                    @endforelse
                </div>
            </div>

            <div class="p-6 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Varian Produk</h2>
                    <button type="button" @click="$dispatch('open-product-variant')"
                        class="inline-flex items-center rounded-md bg-persada-primary px-3 py-2 text-sm font-semibold text-white hover:bg-persada-dark-hover">
                        <x-heroicon-o-plus class="w-4 h-4 sm:-ml-1 sm:mr-2" />
                        <span class="hidden sm:inline">Tambah Varian</span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                                    Nama Varian</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                                    SKU</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                                    Harga</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                                    Stok</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($product->variants as $variant)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                        {{ $variant->name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                        {{ $variant->sku ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                        Rp {{ number_format($variant->price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                        {{ $variant->stock }}
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                                            Dipesan: {{ $variant->reserved_stock }} &middot; Tersedia:
                                            {{ $variant->available_stock }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <div class="flex items-center gap-x-2">
                                            <button type="button" title="Catat Mutasi Stok"
                                                @click="$dispatch('open-stock-movement', { productId: '{{ $product->id }}', variantId: '{{ $variant->id }}' })"
                                                class="p-1.5 rounded bg-persada-primary text-white hover:bg-persada-dark-hover">
                                                <x-heroicon-o-arrows-right-left class="w-4 h-4" />
                                            </button>

                                            <form
                                                action="{{ route('master.products.variants.destroy', [$product, $variant]) }}"
                                                method="POST">
                                                @csrf @method('DELETE')
                                                <button type="button" onclick="confirmDelete(this.closest('form'))"
                                                    title="Hapus Varian"
                                                    class="p-1.5 rounded bg-red-600 text-white hover:bg-red-700">
                                                    <x-heroicon-o-trash class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Belum ada varian produk.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-6 bg-white rounded-lg shadow-sm dark:bg-gray-800">
                <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Riwayat Mutasi Stok</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">10 perubahan stok terakhir untuk
                            produk ini.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('master.stock.index', ['product_id' => $product->id]) }}"
                            class="text-sm font-medium text-persada-primary hover:underline">
                            Lihat semua
                        </a>
                    </div>
                </div>
                @include('pages.dashboard.stock.partials.movements-table')
            </div>
        </div>

        @include('pages.dashboard.product.partials.image-form')
        @include('pages.dashboard.product.partials.variant-form')
        @include('pages.dashboard.stock.partials.form', ['products' => collect([$product])])
    </div>
@endsection
