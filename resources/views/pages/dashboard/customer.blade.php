@extends('layouts.admin')

@section('title', 'Data Pelanggan')

@section('content')
    <div x-data="customerModal()">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Data Pelanggan</h1>
        </div>

        <div class="mt-6 overflow-x-auto bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            No.</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            Nama</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            Email</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            Tgl. Bergabung</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                {{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">
                                {{ $customer->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $customer->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $customer->created_at->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                <button @click="openModal('{{ $customer->id }}')" title="Lihat Detail"
                                    class="p-1.5 rounded bg-blue-500 text-white hover:bg-blue-600">
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Belum
                                ada data pelanggan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $customers->links() }}
        </div>

        <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" x-cloak>

            <div @click.away="closeModal()" x-show="isOpen" x-transition
                class="w-full max-w-3xl max-h-[90vh] bg-white rounded-lg shadow-xl dark:bg-gray-800 flex flex-col">

                <div
                    class="px-8 py-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detail Pelanggan</h3>
                    <button @click="closeModal()"
                        class="p-1 rounded-full text-gray-500 hover:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-700">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-8 flex-grow overflow-y-auto">
                    <div x-show="isLoading" class="flex justify-center items-center h-full">
                        <div class="w-8 h-8 border-4 border-persada-primary border-t-transparent rounded-full animate-spin">
                        </div>
                    </div>

                    <div x-show="!isLoading && selectedCustomer" class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-3">
                                <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Informasi Profil</h4>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Nama</dt>
                                        <dd class="text-base font-medium text-gray-900 dark:text-white"
                                            x-text="selectedCustomer?.name"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                        <dd class="text-base text-gray-900 dark:text-white"
                                            x-text="selectedCustomer?.email"></dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">No. Telepon</dt>
                                        <dd class="text-base text-gray-900 dark:text-white"
                                            x-text="selectedCustomer?.phone || '-'"></dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="space-y-3">
                                <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Alamat Utama</h4>
                                <template x-if="selectedCustomer?.addresses.find(addr => addr.is_primary)">
                                    <div class="text-base text-gray-800 dark:text-gray-200">
                                        <template x-for="address in selectedCustomer.addresses" :key="address.id">
                                            <div x-show="address.is_primary">
                                                <p class="font-semibold" x-text="address.receiver_name"></p>
                                                <p class="text-gray-600 dark:text-gray-300" x-text="address.phone"></p>
                                                <p class="text-gray-600 dark:text-gray-300" x-text="address.street_address">
                                                </p>
                                                <p class="text-gray-600 dark:text-gray-300" x-text="address.area_name"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template
                                    x-if="!selectedCustomer || selectedCustomer?.addresses.length === 0 || !selectedCustomer?.addresses.find(addr => addr.is_primary)">
                                    <p class="text-base text-gray-500 dark:text-gray-400">Tidak ada alamat utama.</p>
                                </template>
                            </div>
                        </div>

                        <hr class="border-gray-200 dark:border-gray-700">

                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Riwayat Pesanan (3 Terakhir)</h4>
                            </div>
                            <div class="space-y-3">
                                <template x-if="selectedCustomer?.orders.length > 0">
                                    <template x-for="order in selectedCustomer.orders" :key="order.id">
                                        <div
                                            class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                            <div class="flex items-center gap-4">
                                                <div class="text-sm">
                                                    <a href="#"
                                                        class="font-semibold text-gray-900 dark:text-white hover:underline"
                                                        x-text="`#${order.order_number}`"></a>
                                                    <p class="text-gray-500 dark:text-gray-400"
                                                        x-text="new Date(order.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })">
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-right flex flex-col items-end gap-y-1">
                                                <p class="font-semibold text-gray-900 dark:text-white"
                                                    x-text="`Rp ${Number(order.grand_total).toLocaleString('id-ID')}`"></p>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                    :class="order.status_badge_class" x-text="order.status_label">
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                                <template x-if="!selectedCustomer || selectedCustomer?.orders.length === 0">
                                    <div class="text-center py-8">
                                        <p class="text-gray-500 dark:text-gray-400">Tidak ada riwayat pesanan.</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function customerModal() {
            return {
                isOpen: false,
                isLoading: false,
                selectedCustomer: null,

                async openModal(customerId) {
                    this.isOpen = true;
                    this.isLoading = true;
                    this.selectedCustomer = null;

                    try {
                        const url = `{{ url('/dashboard/master/customers/details') }}/${customerId}`;
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        });

                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                        const data = await response.json();
                        this.selectedCustomer = data;

                    } catch (error) {
                        console.error('Gagal mengambil data pelanggan:', error);
                        alert('Gagal memuat data pelanggan. Silakan coba lagi.');
                        this.closeModal();
                    } finally {
                        this.isLoading = false;
                    }
                },

                closeModal() {
                    this.isOpen = false;
                    this.selectedCustomer = null;
                }
            }
        }
    </script>
@endpush
