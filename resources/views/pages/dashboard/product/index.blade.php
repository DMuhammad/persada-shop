@extends('layouts.admin')

@section('title', 'Manajemen Produk')

@section('content')
    <div x-data="productCrud()" x-init="init()">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Manajemen Produk</h1>
            <button @click="openModal('add')"
                class="inline-flex items-center justify-center rounded-md bg-persada-primary p-2 text-sm font-semibold text-white hover:bg-persada-dark-hover sm:px-4">
                <x-heroicon-o-plus class="h-5 w-5" />
                <span class="hidden sm:ml-2 sm:inline">Tambah Produk</span>
            </button>
        </div>

        <div class="mt-6 overflow-x-auto bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                            No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                            Nama Produk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                            Kategori</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                            Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @forelse ($products as $product)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                {{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                {{ $product->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                {{ $product->category->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    class="px-2 inline-flex text-xs font-semibold rounded-full
                                {{ $product->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-800' }}">
                                    {{ ucfirst($product->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <div class="flex items-center gap-x-2">
                                    <a href="{{ route('master.products.detail', $product->id) }}" title="Detail Produk"
                                        class="p-1.5 rounded bg-blue-500 text-white hover:bg-blue-600">
                                        <x-heroicon-o-eye class="w-4 h-4" />
                                    </a>

                                    <button @click="openModal('edit', @js($product))" title="Edit Produk"
                                        class="p-1.5 rounded bg-amber-500 text-white hover:bg-amber-600">
                                        <x-heroicon-o-pencil class="w-4 h-4" />
                                    </button>

                                    <form action="{{ route('master.products.destroy', $product->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" onclick="confirmDelete(this.closest('form'))"
                                            title="Hapus Produk"
                                            class="p-1.5 rounded bg-red-600 text-white hover:bg-red-700">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Belum ada produk.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>

        @include('pages.dashboard.product.partials.form')
    </div>
@endsection
