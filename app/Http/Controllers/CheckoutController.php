<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\UserAddress;
use App\Services\BiteshipService;
use App\Services\PaymentService;
use App\Services\Stock\StockService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckoutController extends Controller
{
    protected $biteship;

    public function __construct(BiteshipService $biteship)
    {
        $this->biteship = $biteship;
    }

    private function getItemsForCheckout()
    {
        $user = auth()->user();
        $buyNowCartItemId = session('buy_now_cart_item_id');

        if ($buyNowCartItemId) {
            return CartItem::with(['productVariant.product.primaryImage'])
                ->where('id', $buyNowCartItemId)
                ->where('user_id', $user->id)
                ->get();
        }

        return CartItem::with(['productVariant.product.primaryImage'])
            ->where('user_id', $user->id)
            ->where('selected', true)
            ->get();
    }

    public function index()
    {
        $user = auth()->user();
        $cartItems = $this->getItemsForCheckout();

        if ($cartItems->isEmpty()) {
            session()->forget('buy_now_cart_item_id');
            return redirect()->route('cart.index')->with('error', 'Pilih produk di keranjang terlebih dahulu.');
        }

        $addresses = $user->addresses()->latest()->get();
        $primaryAddress = $addresses->where('is_primary', true)->first() ?? $addresses->first();
        $settings = Setting::pluck('value', 'key');
        $paymentOptions = [
            'payment_bca_va_active' => 'BCA Virtual Account',
            'payment_bri_va_active' => 'BRI Virtual Account',
            'payment_bni_va_active' => 'BNI Virtual Account',
            'payment_mandiri_va_active' => 'Mandiri Virtual Account',
            'payment_qris_active' => 'QRIS',
            'payment_gopay_active' => 'GoPay',
            'payment_shopeepay_active' => 'ShopeePay',
        ];

        $activePaymentMethods = [];
        foreach ($paymentOptions as $key => $label) {
            if (!empty($settings[$key]) && $settings[$key] == '1') {
                $code = str_replace(['payment_', '_active'], '', $key);
                $activePaymentMethods[$code] = $label;
            }
        }

        return view('pages.home.checkout', compact('cartItems', 'addresses', 'primaryAddress', 'activePaymentMethods'));
    }

    public function calculateShipping(Request $request)
    {
        $validated = $request->validate(['address_id' => 'required|exists:user_addresses,id']);
        $destinationAddress = UserAddress::where('user_id', auth()->id())->findOrFail($validated['address_id']);

        $settings = Setting::pluck('value', 'key');
        $originAreaId = $settings['shipping_origin_area_id'] ?? null;

        if (!$originAreaId) {
            return response()->json(['success' => false, 'message' => 'Alamat asal pengiriman belum diatur.'], 400);
        }

        $cartItems = $this->getItemsForCheckout();
        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Keranjang kosong.'], 400);
        }

        $items = $cartItems->map(fn($item) => [
            'name'        => $item->productVariant->product->name,
            'description' => $item->productVariant->name,
            'value'       => (int) $item->productVariant->price,
            'quantity'    => $item->quantity,
            'weight'      => $item->productVariant->weight_in_grams ?? 100,
        ])->toArray();

        $activeCouriers = ['sicepat', 'anteraja', 'jne', 'jnt'];
        $shippingRates = $this->biteship->getRates($originAreaId, $destinationAddress->area_id, $items, implode(',', $activeCouriers));

        return response()->json($shippingRates);
    }

    public function store(Request $request, PaymentService $paymentService, StockService $stockService)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'address_id' => 'required|exists:user_addresses,id,user_id,' . $user->id,
            'shipping_rate' => 'required|json',
            'payment_method' => 'required|string',
        ]);

        $shippingRate = json_decode($validated['shipping_rate'], true);
        if (!isset($shippingRate['price'], $shippingRate['courier_name'], $shippingRate['courier_service_name'])) {
            return response()->json(['message' => 'Opsi pengiriman tidak valid.'], 422);
        }

        $cartItems = $this->getItemsForCheckout();
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Sesi checkout Anda telah berakhir. Silakan muat ulang halaman.'], 400);
        }

        try {
            $order = DB::transaction(function () use ($user, $cartItems, $validated, $shippingRate, $stockService) {
                $address = UserAddress::findOrFail($validated['address_id']);
                $subtotal = $cartItems->sum(fn($item) => $item->productVariant->price * $item->quantity);
                $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

                $order = Order::create([
                    'order_number' => $orderNumber,
                    'user_id' => $user->id,
                    'shipping_address' => $address->toArray(),
                    'shipping_courier' => $shippingRate['courier_name'],
                    'shipping_service' => $shippingRate['courier_service_name'],
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingRate['price'],
                    'grand_total' => $subtotal + $shippingRate['price'],
                    'status' => 'pending_payment',
                    'payment_method' => $validated['payment_method'],
                ]);

                foreach ($cartItems as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_variant_id' => $item->product_variant_id,
                        'product_name' => $item->productVariant->product->name,
                        'variant_name' => $item->productVariant->name,
                        'sku' => $item->productVariant->sku,
                        'price_at_purchase' => $item->productVariant->price,
                        'quantity' => $item->quantity,
                        'weight_in_grams' => $item->productVariant->weight_in_grams ?? 100,
                    ]);
                    $stockService->reserve($item->productVariant, $item->quantity);
                }

                CartItem::whereIn('id', $cartItems->pluck('id'))->delete();
                session()->forget('buy_now_cart_item_id');

                try {
                    $admins = User::whereIn('role', ['admin', 'superadmin'])->get();
                    if ($admins->isNotEmpty()) {
                        Notification::send($admins, new NewOrderNotification($order));
                    }
                } catch (Exception $e) {
                    Log::error('Gagal mengirim notifikasi pesanan baru: ' . $e->getMessage());
                }

                return $order;
            });

            $paymentService->createTransaction($order);
            $order->refresh();

            return response()->json([
                'snap_token' => $order->payment_token,
                'order_id'   => $order->order_number,
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Gagal membuat pesanan: ' . $e->getMessage()], 500);
        }
    }
}
