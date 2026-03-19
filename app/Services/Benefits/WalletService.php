<?php

namespace App\Services\Benefits;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WalletService
{
    public function __construct(private readonly BenefitsFeatureService $featureService)
    {
    }

    public function historyForUser(User $user, int $perPage = 12): LengthAwarePaginator
    {
        $this->featureService->ensureWalletEnabled();

        return Wallet::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }
}
