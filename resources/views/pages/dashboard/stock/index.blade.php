@extends('layouts.admin')

@section('title', 'Mutasi Stok')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Mutasi Stok</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Seluruh riwayat perubahan stok, baik stok masuk,
                penjualan, maupun koreksi.</p>
        </div>
        <button x-data @click="$dispatch('open-stock-movement')"
            class="inline-flex items-center justify-center rounded-md bg-persada-primary p-2 text-sm font-semibold text-white hover:bg-persada-dark-hover sm:px-4">
            <x-heroicon-o-plus class="h-5 w-5" />
            <span class="hidden sm:ml-2 sm:inline">Catat Mutasi</span>
        </button>
    </div>

    <form method="GET" class="mt-6 grid grid-cols-1 gap-3 rounded-lg bg-white p-4 shadow-sm sm:grid-cols-5 dark:bg-gray-800">
        <div class="sm:col-span-2">
            <label for="filter-product" class="block text-xs font-medium text-gray-600 dark:text-gray-400">Produk</label>
            <select id="filter-product" name="product_id"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700">
                <option value="">Semua produk</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? null) === $product->id)>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-type" class="block text-xs font-medium text-gray-600 dark:text-gray-400">Jenis</label>
            <select id="filter-type" name="type"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700">
                <option value="">Semua jenis</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(filled($filters['type'] ?? null) && (int) $filters['type'] === $type->value)>
                        {{ $type->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-start" class="block text-xs font-medium text-gray-600 dark:text-gray-400">Dari</label>
            <input id="filter-start" type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700">
        </div>
        <div>
            <label for="filter-end" class="block text-xs font-medium text-gray-600 dark:text-gray-400">Sampai</label>
            <input id="filter-end" type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700">
        </div>
        <div class="flex items-end gap-2 sm:col-span-5">
            <button
                class="inline-flex items-center rounded-md bg-persada-primary px-4 py-2 text-sm font-semibold text-white hover:bg-persada-dark-hover">
                <x-heroicon-o-funnel class="mr-2 h-4 w-4" />
                Terapkan
            </button>
            <a href="{{ route('master.stock.index') }}"
                class="rounded-md px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                Reset
            </a>
        </div>
    </form>

    <div class="mt-6 bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
        @include('pages.dashboard.stock.partials.movements-table')
    </div>

    <div class="mt-4">
        {{ $movements->links() }}
    </div>

    @include('pages.dashboard.stock.partials.form')
@endsection
