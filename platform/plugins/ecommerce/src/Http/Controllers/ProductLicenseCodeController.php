<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Enums\ProductLicenseCodeStatusEnum;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\ProductLicenseCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductLicenseCodeController extends BaseController
{
    public function index(Product $product)
    {
        if (! EcommerceHelper::isEnabledLicenseCodesForDigitalProducts()) {
            abort(404);
        }

        $this->pageTitle(trans('plugins/ecommerce::products.license_codes.title') . ' - ' . $product->name);

        $licenseCodes = $product->licenseCodes()
            ->with(['assignedOrderProduct.order'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Check for low stock warning
        $availableCount = $product->availableLicenseCodes()->count();
        $showLowStockWarning = $product->license_code_type === 'pick_from_list' && $availableCount <= 5 && $availableCount > 0;
        $showOutOfStockWarning = $product->license_code_type === 'pick_from_list' && $availableCount === 0;

        return view('plugins/ecommerce::products.license-codes.index', compact(
            'product',
            'licenseCodes',
            'availableCount',
            'showLowStockWarning',
            'showOutOfStockWarning'
        ));
    }

    public function store(Product $product, Request $request): BaseHttpResponse
    {
        if (! EcommerceHelper::isEnabledLicenseCodesForDigitalProducts()) {
            abort(404);
        }

        $request->validate([
            'license_code' => 'required|string|max:255|unique:ec_product_license_codes,license_code',
        ]);

        $product->licenseCodes()->create([
            'license_code' => $request->input('license_code'),
            'status' => ProductLicenseCodeStatusEnum::AVAILABLE,
        ]);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::products.license_codes.created_successfully'));
    }

    public function update(Product $product, ProductLicenseCode $licenseCode, Request $request): BaseHttpResponse
    {
        if (! EcommerceHelper::isEnabledLicenseCodesForDigitalProducts()) {
            abort(404);
        }

        if ($licenseCode->product_id !== $product->id) {
            abort(404);
        }

        if ($licenseCode->isUsed()) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/ecommerce::products.license_codes.cannot_edit_used_code'));
        }

        $request->validate([
            'license_code' => 'required|string|max:255|unique:ec_product_license_codes,license_code,' . $licenseCode->id,
        ]);

        $licenseCode->update([
            'license_code' => $request->input('license_code'),
        ]);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::products.license_codes.updated_successfully'));
    }

    public function destroy(Product $product, ProductLicenseCode $licenseCode): BaseHttpResponse
    {
        if (! EcommerceHelper::isEnabledLicenseCodesForDigitalProducts()) {
            abort(404);
        }

        if ($licenseCode->product_id !== $product->id) {
            abort(404);
        }

        if ($licenseCode->isUsed()) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/ecommerce::products.license_codes.cannot_delete_used_code'));
        }

        $licenseCode->delete();

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/ecommerce::products.license_codes.deleted_successfully'));
    }

    public function bulkGenerate(Product $product, Request $request): BaseHttpResponse
    {
        if (! EcommerceHelper::isEnabledLicenseCodesForDigitalProducts()) {
            abort(404);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'format' => 'required|string|in:uuid,alphanumeric,numeric,custom',
            'pattern' => 'required_if:format,custom|string|max:50',
        ]);

        $quantity = $request->input('quantity');
        $format = $request->input('format');
        $pattern = $request->input('pattern');

        $generatedCodes = [];
        $duplicateCount = 0;

        for ($i = 0; $i < $quantity; $i++) {
            $code = $this->generateLicenseCode($format, $pattern);

            // Check for duplicates
            if (ProductLicenseCode::where('license_code', $code)->exists()) {
                $duplicateCount++;

                continue;
            }

            $generatedCodes[] = [
                'product_id' => $product->id,
                'license_code' => $code,
                'status' => ProductLicenseCodeStatusEnum::AVAILABLE,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($generatedCodes)) {
            ProductLicenseCode::insert($generatedCodes);
        }

        $message = trans('plugins/ecommerce::products.license_codes.generated_successfully', [
            'count' => count($generatedCodes),
        ]);

        if ($duplicateCount > 0) {
            $message .= ' ' . trans('plugins/ecommerce::products.license_codes.duplicates_skipped', [
                'count' => $duplicateCount,
            ]);
        }

        return $this
            ->httpResponse()
            ->setMessage($message);
    }

    private function generateLicenseCode(string $format, ?string $pattern = null): string
    {
        return match ($format) {
            'uuid' => Str::uuid()->toString(),
            'alphanumeric' => $this->generateAlphanumeric(12),
            'numeric' => $this->generateNumeric(12),
            'custom' => $this->generateCustomPattern($pattern),
            default => Str::uuid()->toString(),
        };
    }

    private function generateAlphanumeric(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $result;
    }

    private function generateNumeric(int $length): string
    {
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= random_int(0, 9);
        }

        return $result;
    }

    private function generateCustomPattern(?string $pattern): string
    {
        if (! $pattern) {
            return Str::uuid()->toString();
        }

        return preg_replace_callback('/[#Aa]/', function ($matches) {
            return match ($matches[0]) {
                '#' => random_int(0, 9),
                'A' => chr(65 + random_int(0, 25)),
                'a' => chr(97 + random_int(0, 25)),
                default => $matches[0],
            };
        }, $pattern);
    }
}
