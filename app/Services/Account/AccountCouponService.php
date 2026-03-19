<?php

namespace App\Services\Account;

use App\Models\Coupon;
use Illuminate\Support\Collection;

class AccountCouponService
{
    public function activeCoupons(): Collection
    {
        $now = strtotime(date('d-m-Y H:i:s'));

        return Coupon::query()
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->get();
    }
}
