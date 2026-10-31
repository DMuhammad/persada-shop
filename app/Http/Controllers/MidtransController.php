<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Stock\StockService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use App\Models\User;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\PaymentConfirmedNotification;
use Illuminate\Support\Facades\Notification;

class MidtransController extends Controller
{
    public function webhook(Request $request, StockService $stockService)
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');

        $payload = $request->all();

        Log::info('Midtrans Webhook Received:', $payload);

        if (empty($payload['order_id']) || empty($payload['signature_key'])) {
            Log::error('Midtrans Webhook: Invalid payload');
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        try {
            $orderId = $payload['order_id'];
            $signatureKey = hash('sha512', $orderId . $payload['status_code'] . $payload['gross_amount'] . config('services.midtrans.server_key'));

            if ($payload['signature_key'] != $signatureKey) {
                Log::warning('Midtrans Webhook: Invalid signature', ['order_id' => $orderId]);
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            $order = Order::where('order_number', $orderId)->first();
            if (!$order) {
                Log::warning('Midtrans Webhook: Order not found', ['order_id' => $orderId]);
                return response()->json(['message' => 'Order not found'], 404);
            }

            $transactionStatus = $payload['transaction_status'];
            $fraudStatus = $payload['fraud_status'];

            DB::transaction(function () use ($order, $transactionStatus, $fraudStatus, $stockService) {
                if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                    if ($fraudStatus == 'accept') {
                        if ($order->status == 'pending_payment') {
                            $order->status = 'paid';
                            $order->paid_at = now();
                            $order->save();

                            try {
                                $admins = User::whereIn('role', ['admin', 'superadmin'])->get();
                                if ($admins->isNotEmpty()) {
                                    Notification::send($admins, new PaymentConfirmedNotification($order));
                                }
                            } catch (Exception $e) {
                                Log::error('Gagal mengirim notifikasi pembayaran terkonfirmasi: ' . $e->getMessage());
                            }

                            foreach ($order->items as $item) {
                                if ($item->productVariant) {
                                    $stockService->sell($item->productVariant, $item->quantity, $order);
                                }
                            }
                        }
                    }
                } elseif ($transactionStatus == 'expire' || $transactionStatus == 'cancel' || $transactionStatus == 'deny') {
                    if ($order->status == 'pending_payment') {
                        $order->status = 'cancelled';
                        $order->save();

                        try {
                            $admins = User::whereIn('role', ['admin', 'superadmin'])->get();
                            if ($admins->isNotEmpty()) {
                                Notification::send($admins, new OrderCancelledNotification($order));
                            }
                        } catch (Exception $e) {
                            Log::error('Gagal mengirim notifikasi pembatalan pesanan: ' . $e->getMessage());
                        }

                        foreach ($order->items as $item) {
                            if ($item->productVariant) {
                                $stockService->release($item->productVariant, $item->quantity);
                            }
                        }
                    }
                }
            });

            Log::info('Midtrans Webhook: Processed successfully', ['order_id' => $orderId]);
            return response()->json(['message' => 'Notification processed successfully']);
        } catch (\Exception $e) {
            Log::error('Midtrans Webhook: Error processing', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error processing notification'], 500);
        }
    }
}
