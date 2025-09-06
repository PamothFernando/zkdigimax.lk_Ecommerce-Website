<?php

namespace Botble\Ecommerce\Listeners;

use Botble\Ecommerce\Events\OrderCompletedEvent;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductLicenseCode;
use Illuminate\Support\Str;

class GenerateLicenseCodeAfterOrderCompleted
{
    public function handle(OrderCompletedEvent $event): void
    {
        if (! EcommerceHelper::isEnabledLicenseCodesForDigitalProducts()) {
            return;
        }

        if (($order = $event->order) instanceof Order && $order->loadMissing(['products.product'])) {
            $orderProducts = $order->products
                ->where(function ($item) {
                    return $item->product->isTypeDigital() && $item->product->generate_license_code;
                });

            $invoiceItems = $order->invoice->items;
            foreach ($orderProducts as $orderProduct) {
                $licenseCode = null;

                // Check the license code assignment method
                if ($orderProduct->product->license_code_type === 'pick_from_list') {
                    // Try to get an available license code from the pool
                    $availableLicenseCode = ProductLicenseCode::query()
                        ->forProduct($orderProduct->product_id)
                        ->available()
                        ->first();

                    if ($availableLicenseCode) {
                        // Use existing license code from pool
                        $licenseCode = $availableLicenseCode->license_code;
                        $availableLicenseCode->markAsUsed($orderProduct);
                    } else {
                        // No codes available in the pool - log warning and fallback to auto-generate
                        logger()->warning('No available license codes found for product ID: ' . $orderProduct->product_id . '. Falling back to auto-generation.');
                        $licenseCode = Str::uuid();
                    }
                } else {
                    // Auto-generate mode - always generate a new UUID
                    $licenseCode = Str::uuid();
                }

                $orderProduct->license_code = $licenseCode;
                $orderProduct->save();

                $invoiceItem = $invoiceItems->where('reference_id', $orderProduct->product_id)->where('reference_type', Product::class)->first();
                if ($invoiceItem) {
                    $invoiceItem->options = array_merge($invoiceItem->options, [
                        'license_code' => $licenseCode,
                    ]);
                    $invoiceItem->save();
                }
            }
        }
    }
}
