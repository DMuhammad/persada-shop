<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Order\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;

class MidtransController extends Controller
{
    public function webhook(Request $request, OrderStatusService $orderStatus)
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

            // The gateway only ever decides the fate of an order that is still unpaid.
            if ($order->status->awaitsPayment()) {
                if (in_array($transactionStatus, ['capture', 'settlement'], true) && $fraudStatus == 'accept') {
                    $orderStatus->change($order, OrderStatus::PAID);
                } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'], true)) {
                    $orderStatus->change($order, OrderStatus::CANCELLED);
                }
            }

            Log::info('Midtrans Webhook: Processed successfully', ['order_id' => $orderId]);
            return response()->json(['message' => 'Notification processed successfully']);
        } catch (\Exception $e) {
            Log::error('Midtrans Webhook: Error processing', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error processing notification'], 500);
        }
    }
}
