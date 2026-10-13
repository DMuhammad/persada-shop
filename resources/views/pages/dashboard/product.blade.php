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

        <div x-show="isOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div @click.away="closeModal()"
                class="w-full max-w-2xl max-h-[90vh] bg-white rounded-lg p-6 dark:bg-gray-800 flex flex-col">

                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white" x-text="modalTitle"></h3>

                <div class="overflow-y-auto pr-2 flex-1">
                    <form id="productForm" :action="formAction" method="POST" class="space-y-4"
                        @submit.prevent="submitForm">
                        @csrf
                        <template x-if="!isAddMode">
                            <input type="hidden" name="_method" value="PATCH">
                        </template>

                        <div>
                            <label class="block text-sm font-medium">Nama Produk</label>
                            <input type="text" name="name" x-model="formData.name"
                                class="mt-1 w-full rounded border-gray-300 dark:bg-gray-700" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Kategori</label>
                            <select name="category_id" id="category"
                                class="mt-1 w-full rounded border-gray-300 dark:bg-gray-700" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Deskripsi Produk</label>
                            <input id="description" type="hidden" name="description" x-model="formData.description">
                            <trix-editor input="description" x-ref="trixEditor"
                                class="prose dark:prose-invert max-w-none mt-1 block w-full rounded border-gray-300 dark:bg-gray-700 focus:ring-persada-primary focus:border-persada-primary">
                            </trix-editor>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Status</label>
                            <select name="status" x-model="formData.status"
                                class="mt-1 w-full rounded border-gray-300 dark:bg-gray-700" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="flex justify-end gap-2 pt-4 mt-4">
                    <button type="button" @click="closeModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                        Batal
                    </button>
                    <button type="button" @click="submitForm()"
                        class="px-4 py-2 bg-persada-primary text-white rounded hover:bg-persada-dark-hover"
                        x-text="submitText"></button>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        let categorySelect;

        document.addEventListener('DOMContentLoaded', () => {
            categorySelect = new Select('#category').tags().build();
        });

        function productCrud() {
            return {
                isOpen: false,
                isAddMode: true,
                modalTitle: '',
                submitText: '',
                formAction: '',
                formData: {
                    id: null,
                    name: '',
                    description: '',
                    status: 'active'
                },

                init() {
                    this.$refs.trixEditor.addEventListener('trix-change', () => {
                        this.formData.description = this.$refs.trixEditor.value;
                    });
                },

                openModal(mode, product = null) {
                    this.isOpen = true;

                    if (mode === 'add') {
                        this.isAddMode = true;
                        this.modalTitle = 'Tambah Produk Baru';
                        this.submitText = 'Simpan';
                        this.formAction = '{{ route('master.products.store') }}';
                        this.formData = {
                            id: null,
                            name: '',
                            description: '',
                            status: 'active'
                        };
                        categorySelect.clear();

                        this.$nextTick(() => {
                            this.$refs.trixEditor.editor.loadHTML('');
                        });

                    } else {
                        this.isAddMode = false;
                        this.modalTitle = 'Edit Produk';
                        this.submitText = 'Simpan';
                        this.formAction = '{{ route('master.products.update', ['product' => ':id']) }}'.replace(':id',
                            product
                            .id);
                        this.formData = {
                            id: product.id,
                            name: product.name,
                            description: product.description ?? '',
                            status: product.status
                        };
                        categorySelect.setSelected(product.category_id, product.category?.name ?? product.category_id);

                        this.$nextTick(() => {
                            this.$refs.trixEditor.editor.loadHTML(this.formData.description);
                        });
                    }
                },

                closeModal() {
                    this.isOpen = false;
                },

                submitForm() {
                    this.formData.description = this.$refs.trixEditor.value;
                    document.querySelector('#description').value = this.formData.description;
                    document.getElementById('productForm').submit();
                }
            }
        }
    </script>
@endpush
