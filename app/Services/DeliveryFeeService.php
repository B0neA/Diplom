<?php

namespace App\Services;

class DeliveryFeeService
{
    public const FREE_DELIVERY_FROM = 800;

    public const SERVICE_FEE = 50;

    /** @var array<int, int> порог суммы блюд => доставка */
    protected const TIERS = [
        800 => 0,
        600 => 99,
        400 => 149,
        0 => 199,
    ];

    public function deliveryFeeForSubtotal(int $itemsSubtotal): int
    {
        $amount = max(0, $itemsSubtotal);

        foreach ([800, 600, 400, 0] as $from) {
            if ($amount >= $from) {
                return self::TIERS[$from];
            }
        }

        return 199;
    }

    public function orderTotalForSubtotal(int $itemsSubtotal): int
    {
        return $itemsSubtotal
            + $this->deliveryFeeForSubtotal($itemsSubtotal)
            + self::SERVICE_FEE;
    }
}
