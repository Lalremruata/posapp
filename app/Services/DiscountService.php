<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Product;
use App\Models\Customer;
use App\Models\AppliedDiscount;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DiscountService
{
    /**
     * Find all applicable discounts for a product
     *
     * @param Product $product The product to check
     * @param int $quantity The quantity being purchased
     * @param Customer|null $customer The customer making the purchase (optional)
     * @return Collection Collection of applicable discounts
     */
    public function findApplicableDiscounts(Product $product, int $quantity = 1, ?Customer $customer = null): Collection
    {
        $now = Carbon::now();

        // Start with product-specific discounts
        $discounts = Discount::whereHas('products', function ($query) use ($product) {
            $query->where('product_id', $product->id);
        })
            ->where('is_active', true)
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($now) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->where(function ($query) {
                $query->whereNull('max_uses')
                    ->orWhereRaw('used_count < max_uses');
            });

        // Add category discounts if the product has a category relationship
        if (method_exists($product, 'category')) {
            $categoryDiscounts = Discount::whereHas('categories', function ($query) use ($product) {
                $query->whereIn('category_id', $product->category()->pluck('id'));
            })
                ->where('is_active', true)
                ->where('min_quantity', '<=', $quantity)
                ->where(function ($query) use ($now) {
                    $query->whereNull('start_date')
                        ->orWhere('start_date', '<=', $now);
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $now);
                })
                ->where(function ($query) {
                    $query->whereNull('max_uses')
                        ->orWhereRaw('used_count < max_uses');
                });

            $discounts = $discounts->union($categoryDiscounts);
        }


        // Order by priority (highest first)
        return $discounts->orderBy('priority', 'desc')->get();
    }

    /**
     * Calculate the best discount for a product
     *
     * @param Product $product The product
     * @param float $price The current price
     * @param int $quantity The quantity being purchased
     * @param Customer|null $customer The customer (optional)
     * @return array [discounted_price, discount_amount, discount]
     */
    public function calculateBestDiscount(Product $product, float $price, int $quantity = 1, ?Customer $customer = null): array
    {
        $applicableDiscounts = $this->findApplicableDiscounts($product, $quantity, $customer);

        if ($applicableDiscounts->isEmpty()) {
            return [
                'discounted_price' => $price,
                'discount_amount' => 0,
                'discount' => null
            ];
        }

        $bestDiscount = null;
        $maxDiscountAmount = 0;

        foreach ($applicableDiscounts as $discount) {
            $discountAmount = $discount->calculateDiscount($price, $quantity);

            if ($discountAmount > $maxDiscountAmount) {
                $maxDiscountAmount = $discountAmount;
                $bestDiscount = $discount;
            }
        }

        // If we found a best discount
        if ($bestDiscount) {
            $discountedPrice = $price - $maxDiscountAmount;

            return [
                'discounted_price' => max(0, $discountedPrice), // Ensure price doesn't go below 0
                'discount_amount' => $maxDiscountAmount,
                'discount' => $bestDiscount
            ];
        }

        // No discount applied
        return [
            'discounted_price' => $price,
            'discount_amount' => 0,
            'discount' => null
        ];
    }

    /**
     * Apply discount to a sale item and record it
     *
     * @param Sale $sale The sale record
     * @param SaleItem $saleItem The sale item
     * @param Product $product The product
     * @param float $originalPrice The original price
     * @param int $quantity The quantity
     * @param Customer|null $customer The customer
     * @return AppliedDiscount|null The applied discount record
     */
    public function applyDiscount(Sale $sale, SaleItem $saleItem, Product $product, float $originalPrice, int $quantity, ?Customer $customer = null): ?AppliedDiscount
    {
        $result = $this->calculateBestDiscount($product, $originalPrice, $quantity, $customer);

        if ($result['discount_amount'] <= 0 || !$result['discount']) {
            return null;
        }

        // Record the applied discount
        $appliedDiscount = AppliedDiscount::create([
            'sale_id' => $sale->id,
            'sale_item_id' => $saleItem->id,
            'discount_id' => $result['discount']->id,
            'original_price' => $originalPrice,
            'discount_amount' => $result['discount_amount'],
            'final_price' => $result['discounted_price'],
        ]);

        // Increment the discount usage count
        $result['discount']->incrementUsage();

        return $appliedDiscount;
    }
}
