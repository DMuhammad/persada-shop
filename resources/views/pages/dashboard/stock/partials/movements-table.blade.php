{{-- Shared movement history table. Expects $movements. --}}
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Waktu
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Produk /
                    Varian</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Jenis
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                    Saldo Sebelum</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Jumlah
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                    Saldo Sesudah</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Referensi
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Oleh</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
            @forelse ($movements as $movement)
                @php
                    $variant = $movement->stock->productVariant ?? null;
                    // The sign lives on the row, so a correction shows its own direction.
                    $isIncoming = $movement->quantity > 0;
                @endphp
                <tr>
                    <td class="px-4 py-3 text-sm whitespace-nowrap text-gray-900 dark:text-gray-200">
                        {{ $movement->created_at->translatedFormat('d M Y H:i') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-200">
                        {{ $variant->product->name ?? '-' }}
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $variant->name ?? '-' }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $isIncoming ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' }}">
                            {{ $movement->type->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">
                        {{ $movement->balance_before }}
                    </td>
                    <td
                        class="px-4 py-3 text-sm text-right font-semibold {{ $isIncoming ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $isIncoming ? '+' : '' }}{{ $movement->quantity }}
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-gray-200">
                        {{ $movement->balance_after }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-200">
                        @if ($movement->order)
                            <a href="{{ route('dashboard.orders.show', $movement->order->order_number) }}"
                                class="font-medium text-persada-primary hover:underline">
                                {{ $movement->order->order_number }}
                            </a>
                        @endif
                        @if ($movement->note)
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $movement->note }}</span>
                        @endif
                        @unless ($movement->order || $movement->note)
                            -
                        @endunless
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ $movement->creator->name ?? 'Sistem' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        Belum ada mutasi stok.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
