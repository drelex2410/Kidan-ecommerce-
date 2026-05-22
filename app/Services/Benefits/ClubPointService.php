<?php

namespace App\Services\Benefits;

use App\Models\ClubPoint;
use App\Models\User;
use App\Models\Wallet;
use App\Support\ClubPointMath;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ClubPointService
{
    public function __construct(private readonly BenefitsFeatureService $featureService)
    {
    }

    public function historyForUser(User $user, int $perPage = 12): LengthAwarePaginator
    {
        $this->featureService->ensureClubPointEnabled();

        return ClubPoint::query()
            ->with('combined_order')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function convertToWallet(User $user, int $clubPointId): int
    {
        $this->featureService->ensureClubPointEnabled();

        $clubPoint = ClubPoint::query()
            ->with('combined_order.orders')
            ->findOrFail($clubPointId);

        if ((int) $clubPoint->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException();
        }

        $combinedOrder = $clubPoint->combined_order;

        if (!$combinedOrder || $combinedOrder->orders()->where('payment_status', 'unpaid')->exists()) {
            return 3;
        }

        if ((int) $clubPoint->convert_status === 1) {
            return 1;
        }

        $rate = ClubPointMath::normalize(get_setting('club_point_convert_rate', 1));
        if (ClubPointMath::compare($rate, '0') === 0) {
            $rate = ClubPointMath::normalize(1);
        }
        $walletAmount = ClubPointMath::divide($clubPoint->points, $rate);

        DB::transaction(function () use ($user, $clubPoint, $walletAmount) {
            $clubPoint->convert_status = 1;
            $clubPoint->save();

            $user->balance = (float) $user->balance + ClubPointMath::toFloat($walletAmount);
            $user->club_points = ClubPointMath::clampToZero(
                ClubPointMath::subtract($user->club_points, $clubPoint->points)
            );
            $user->save();

            if (Schema::hasTable('wallets')) {
                Wallet::query()->create([
                    'user_id' => $user->id,
                    'amount' => ClubPointMath::toFloat($walletAmount),
                    'payment_method' => 'Club Point Converted',
                    'payment_details' => 'Club Point Converted',
                    'details' => 'Club Point Converted',
                    'type' => 'Added',
                    'approval' => 1,
                ]);
            }
        });

        return 1;
    }
}
