<?php

namespace App\Services\Benefits;

use App\Contracts\AddonStatusManager;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BenefitsFeatureService
{
    public function __construct(private readonly AddonStatusManager $addonStatusManager)
    {
    }

    public function ensureRefundEnabled(): void
    {
        if (Schema::hasTable('addons') && !$this->addonStatusManager->isActivated('refund')) {
            throw new HttpException(403, 'Refund requests are not available.');
        }
    }

    public function ensureWalletEnabled(): void
    {
        if ((int) get_setting('wallet_system') !== 1) {
            throw new HttpException(403, 'Wallet is not available.');
        }
    }

    public function ensureClubPointEnabled(): void
    {
        if ((int) get_setting('club_point') !== 1) {
            throw new HttpException(403, 'Club points are not available.');
        }
    }

    public function ensureAffiliateEnabled(): void
    {
        if ((int) get_setting('affiliate_system') !== 1) {
            throw new HttpException(403, 'Affiliate is not available.');
        }
    }
}
