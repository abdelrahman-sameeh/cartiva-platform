<?php

namespace App\Services\Shipping;

use App\Models\Order\Order;
use App\Models\Order\SubOrder;
use App\Models\OrderCouponAllocation;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Services\Payments\RefundService;
use App\Support\ProductVariantOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DemoShippingService
{
    public function __construct(private readonly RefundService $refunds) {}

    public function createForSubOrder(SubOrder $subOrder): Shipment
    {
        $subOrder->loadMissing('order');
        $isCash = $subOrder->order->payment_method === 'cash';
        $gross = round((float) $subOrder->total_price, 2);
        $platformFee = $isCash
            ? round($gross * $this->basisPoints('platform_commission_bps') / 10_000, 2)
            : 0;
        $shippingFee = $isCash ? round((float) config('services.demo_shipping.shipping_fee', 0), 2) : 0;
        $collectionFee = $isCash
            ? round($gross * $this->basisPoints('cod_collection_bps') / 10_000, 2)
            : 0;

        if ($isCash && $platformFee + $shippingFee + $collectionFee > $gross) {
            throw new \LogicException('رسوم شحنة الدفع عند الاستلام أكبر من قيمة الطلب.');
        }

        return $subOrder->shipment()->firstOrCreate([], [
            'provider' => 'demo_shipping',
            'tracking_number' => 'DEMO-'.$subOrder->id.'-'.Str::upper(Str::random(8)),
            'status' => 'created',
            'cod_status' => $isCash ? 'awaiting_collection' : 'not_applicable',
            'cod_amount' => $isCash ? $gross : 0,
            'platform_fee' => $platformFee,
            'shipping_fee' => $shippingFee,
            'collection_fee' => $collectionFee,
            'seller_net' => $isCash ? round($gross - $platformFee - $shippingFee - $collectionFee, 2) : 0,
        ]);
    }

    public function handleEvent(string $trackingNumber, string $event): Shipment
    {
        return DB::transaction(function () use ($trackingNumber, $event): Shipment {
            $shipment = Shipment::query()
                ->with('subOrder.order')
                ->where('tracking_number', $trackingNumber)
                ->lockForUpdate()
                ->firstOrFail();

            match ($event) {
                'picked-up' => $this->markPickedUp($shipment),
                'in-transit' => $this->markInTransit($shipment),
                'delivered' => $this->markDelivered($shipment),
                'remitted' => $this->markRemitted($shipment),
                'returned' => $this->markReturned($shipment),
                'seller-paid' => $this->markSellerPaid($shipment),
                default => throw ValidationException::withMessages([
                    'event' => 'حدث شركة الشحن التجريبية غير معروف.',
                ]),
            };

            $this->syncOrder($shipment->subOrder->order);

            return $shipment->refresh();
        });
    }

    public function cancel(SubOrder $subOrder): Shipment
    {
        return DB::transaction(fn (): Shipment => $this->cancelLocked($subOrder));
    }

    private function cancelLocked(SubOrder $subOrder): Shipment
    {
        $subOrder = SubOrder::query()->with('order')->lockForUpdate()->findOrFail($subOrder->id);
        $shipment = $subOrder->shipment()->lockForUpdate()->first()
            ?? $this->createForSubOrder($subOrder);

        if ($shipment->status === 'cancelled') {
            return $shipment;
        }

        if (! in_array($shipment->status, ['created'], true)) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن إلغاء الطلب بعد استلام شركة الشحن له.',
            ]);
        }

        $shipment->update(['status' => 'cancelled']);
        $subOrder->update(['status' => 'cancelled']);
        $this->restoreInventory($subOrder);
        $this->finalizeCancellation($subOrder);
        $this->syncOrder($subOrder->order);

        return $shipment->refresh();
    }

    private function markPickedUp(Shipment $shipment): void
    {
        if ($shipment->status === 'picked_up') {
            return;
        }

        $this->requireStatus($shipment, ['created']);
        $shipment->update(['status' => 'picked_up', 'picked_up_at' => now()]);
        $shipment->subOrder->update(['status' => 'processing']);
    }

    private function markInTransit(Shipment $shipment): void
    {
        if ($shipment->status === 'in_transit') {
            return;
        }

        $this->requireStatus($shipment, ['created', 'picked_up']);
        $shipment->update([
            'status' => 'in_transit',
            'picked_up_at' => $shipment->picked_up_at ?? now(),
        ]);
        $shipment->subOrder->update(['status' => 'shipped']);
    }

    private function markDelivered(Shipment $shipment): void
    {
        if ($shipment->status === 'delivered') {
            return;
        }

        $this->requireStatus($shipment, ['created', 'picked_up', 'in_transit']);
        $values = ['status' => 'delivered', 'delivered_at' => now()];

        if ($shipment->cod_status === 'awaiting_collection') {
            $values['cod_status'] = 'collected';
            $values['cod_collected_at'] = now();
        }

        $shipment->update($values);
        $shipment->subOrder->update(['status' => 'completed']);
    }

    private function markRemitted(Shipment $shipment): void
    {
        if ($shipment->cod_status === 'remitted') {
            return;
        }

        if ($shipment->status !== 'delivered' || $shipment->cod_status !== 'collected') {
            throw ValidationException::withMessages([
                'event' => 'لا يمكن تحويل تحصيل COD قبل تسليم الشحنة وتحصيل قيمتها.',
            ]);
        }

        $shipment->update(['cod_status' => 'remitted', 'cod_remitted_at' => now()]);
    }

    private function markReturned(Shipment $shipment): void
    {
        if ($shipment->status === 'returned') {
            return;
        }

        $this->requireStatus($shipment, ['created', 'picked_up', 'in_transit']);
        $shipment->update(['status' => 'returned']);
        $shipment->subOrder->update(['status' => 'cancelled']);
        $this->restoreInventory($shipment->subOrder);
        $this->finalizeCancellation($shipment->subOrder);
    }

    private function markSellerPaid(Shipment $shipment): void
    {
        if ($shipment->seller_paid_at) {
            return;
        }

        if ($shipment->cod_status !== 'remitted') {
            throw ValidationException::withMessages([
                'event' => 'لا يمكن صرف مستحق البائع قبل وصول تحصيل COD للمنصة.',
            ]);
        }

        $shipment->update(['seller_paid_at' => now()]);
    }

    private function restoreInventory(SubOrder $subOrder): void
    {
        $subOrder->loadMissing('items');

        foreach ($subOrder->items as $item) {
            $variant = ProductVariant::query()
                ->when(
                    $item->variant_sku,
                    fn ($query) => $query->where('sku', $item->variant_sku),
                    fn ($query) => $query
                        ->where('product_id', $item->product_id)
                        ->where('option_signature', ProductVariantOptions::signature($item->variant_options ?? []))
                )
                ->lockForUpdate()
                ->first();

            if (! $variant) {
                continue;
            }

            $variant->increment('stock', $item->quantity);
            $product = $variant->product()->lockForUpdate()->first();

            if ($product) {
                $product->update(['sold_count' => max(0, $product->sold_count - $item->quantity)]);
            }
        }
    }

    private function finalizeCancellation(SubOrder $subOrder): void
    {
        $this->refunds->requestForCancelledSubOrder($subOrder);
        $allocation = OrderCouponAllocation::query()
            ->where('sub_order_id', $subOrder->id)
            ->lockForUpdate()
            ->first();

        if ($allocation && ! $allocation->released_at) {
            if ($allocation->coupon && $allocation->coupon->used_count > 0) {
                $allocation->coupon->decrement('used_count');
            }
            $allocation->update(['released_at' => now()]);
        }

        $activeSubOrders = $subOrder->order->subOrders()->where('status', '!=', 'cancelled')->get();
        $subOrder->order->update([
            'subtotal' => $activeSubOrders->sum('subtotal'),
            'discount' => $activeSubOrders->sum('discount'),
            'total_price' => $activeSubOrders->sum('total_price'),
        ]);
    }

    private function syncOrder(Order $order): void
    {
        $order->load('subOrders.shipment');
        $statuses = $order->subOrders->pluck('status');

        $status = match (true) {
            $statuses->every(fn (string $status): bool => $status === 'cancelled') => 'cancelled',
            $statuses->every(fn (string $status): bool => in_array($status, ['completed', 'cancelled'], true)) => 'completed',
            $statuses->contains('shipped') => 'shipped',
            default => 'processing',
        };

        $values = ['status' => $status];

        if ($order->payment_method === 'cash'
            && $statuses->every(fn (string $status): bool => $status === 'completed')
            && $order->subOrders->every(
                fn (SubOrder $subOrder): bool => in_array($subOrder->shipment?->cod_status, ['collected', 'remitted'], true)
            )) {
            $values['payment_status'] = 'paid';
        }

        $order->update($values);
    }

    private function requireStatus(Shipment $shipment, array $allowed): void
    {
        if (! in_array($shipment->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'event' => "لا يمكن تطبيق الحدث على شحنة حالتها {$shipment->status}.",
            ]);
        }
    }

    private function basisPoints(string $key): int
    {
        $basisPoints = (int) config("services.demo_shipping.{$key}", 0);

        if ($basisPoints < 0 || $basisPoints > 10_000) {
            throw new \LogicException("قيمة {$key} يجب أن تكون بين 0 و10000 نقطة أساس.");
        }

        return $basisPoints;
    }
}
