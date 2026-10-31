<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }

        .container {
            padding: 30px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-gray {
            color: #6b7280;
        }

        .text-lg {
            font-size: 18px;
        }

        .text-xl {
            font-size: 24px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .mt-8 {
            margin-top: 32px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .w-full {
            width: 100%;
        }

        .border-collapse {
            border-collapse: collapse;
        }

        .items-table {
            margin-top: 30px;
        }

        .items-table th,
        .items-table td {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table th {
            text-transform: uppercase;
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }

        .summary-table {
            margin-top: 30px;
            width: 50%;
            margin-left: 50%;
        }

        .summary-table td {
            padding: 6px 0;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }

        .status-paid {
            position: absolute;
            top: 150px;
            left: 50px;
            color: #16a34a;
            font-size: 32px;
            font-weight: bold;
            border: 4px solid #16a34a;
            padding: 10px 20px;
            display: inline-block;
            transform: rotate(-20deg);
            opacity: 0.2;
            text-transform: uppercase;
        }

        .internal-notes {
            margin-top: 40px;
            border-top: 2px dashed #e74c3c;
            padding-top: 15px;
            background-color: #fff5f5;
            padding: 15px;
            border-radius: 8px;
        }

        .internal-notes h4 {
            color: #c0392b;
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }

        .internal-notes p {
            margin: 0 0 5px 0;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- Stempel LUNAS (jika sudah bayar) --}}
        @if (!$order->status->awaitsPayment())
            <div class="status-paid">Lunas</div>
        @endif

        {{-- Header --}}
        <table class="w-full">
            <tr>
                <td class="text-left" style="width: 50%;">
                    <h1 style="color: #40916c; font-size: 28px; margin: 0;">Persada Packaging</h1>
                    <p class="text-gray" style="margin: 0;">Jl. Industri Kemasan No. 1<br>Pasuruan, Jawa Timur, 67152
                    </p>
                </td>
                <td class="text-right" style="width: 50%;">
                    <h2 class="text-xl font-bold" style="margin: 0;">INVOICE</h2>
                    <p class="text-gray" style="margin: 0;">#{{ $order->order_number }}</p>
                    <p class="text-gray" style="margin: 0;">{{ $order->created_at->format('d F Y') }}</p>
                </td>
            </tr>
        </table>

        {{-- Info Pelanggan --}}
        <div style="margin-top: 40px;">
            <p class="text-gray">Ditagihkan Kepada:</p>
            <p style="margin-top: 2px;">
                <strong class="font-bold text-lg">{{ $order->shipping_address['receiver_name'] }}</strong><br>
                {{ $order->shipping_address['street_address'] }},<br>
                {{ $order->shipping_address['area_name'] }}<br>
                {{ $order->shipping_address['phone'] }}
            </p>
        </div>

        {{-- Tabel Item --}}
        <table class="w-full items-table border-collapse">
            <thead>
                <tr>
                    <th class="text-left">Produk</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-right">Harga Satuan</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            <span class="font-bold">{{ $item->product_name }}</span>
                            <br><span class="text-gray">{{ $item->variant_name }}</span>
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">Rp {{ number_format($item->price_at_purchase, 0, ',', '.') }}</td>
                        <td class="text-right">Rp
                            {{ number_format($item->price_at_purchase * $item->quantity, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Tabel Summary --}}
        <table class="summary-table">
            <tr>
                <td class="text-gray">Subtotal</td>
                <td class="text-right">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="text-gray">Ongkos Kirim</td>
                <td class="text-right">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td class="font-bold text-lg pt-2">GRAND TOTAL</td>
                <td class="font-bold text-lg text-right pt-2">Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                </td>
            </tr>
        </table>

        <div style="clear: both;"></div>

        {{-- [BAGIAN KHUSUS ADMIN] --}}
        {{-- Cek apakah yang sedang login adalah admin atau superadmin --}}
        @if (Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin']))
            <div class="internal-notes">
                <h4>Catatan Internal (Hanya untuk Admin)</h4>
                <p><strong>Metode Pembayaran:</strong>
                    {{ strtoupper(str_replace('_', ' ', $order->payment_method ?? '-')) }}</p>
                <p><strong>Kurir Pengiriman:</strong> {{ strtoupper($order->shipping_courier ?? '-') }}
                    ({{ $order->shipping_service ?? '-' }})</p>
                <p><strong>Catatan Tambahan dari Pelanggan:</strong> {{ $order->note ?? 'Tidak ada.' }}</p>
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <p>Terima kasih telah berbelanja di Persada Packaging!</p>
        </div>
    </div>
</body>

</html>
