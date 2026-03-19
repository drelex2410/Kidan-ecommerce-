<?php

namespace App\Services\Benefits;

use App\Models\AffiliateLog;
use App\Models\AffiliateOption;
use App\Models\AffiliatePayment;
use App\Models\AffiliateStats;
use App\Models\AffiliateUser;
use App\Models\AffiliateWithdrawRequest;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AffiliateService
{
    public function __construct(private readonly BenefitsFeatureService $featureService)
    {
    }

    public function register(User $user, array $payload): array
    {
        $this->featureService->ensureAffiliateEnabled();

        $profile = AffiliateUser::query()->firstOrNew([
            'user_id' => $user->id,
        ]);

        $information = [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'],
            'address' => $payload['address'],
            'description' => $payload['description'],
        ];

        if (!$user->referral_code) {
            $user->referral_code = strtoupper(Str::random(8));
            $user->save();
        }

        $profile->informations = json_encode($information);
        if (!$profile->exists && Schema::hasColumn('affiliate_users', 'status') && $profile->status === null) {
            $profile->status = 0;
        }
        $profile->save();

        return [
            'success' => true,
            'message' => translate('Your verification request has been submitted successfully!'),
            'data' => null,
        ];
    }

    public function balance(User $user): string
    {
        $this->featureService->ensureAffiliateEnabled();

        return single_price((float) optional($user->affiliate_user)->balance);
    }

    public function referralUrl(User $user): string
    {
        $this->featureService->ensureAffiliateEnabled();

        if (!$user->referral_code) {
            $user->referral_code = strtoupper(Str::random(8));
            $user->save();
        }

        return rtrim((string) config('app.url'), '/') . '/user/registration?referral_code=' . $user->referral_code;
    }

    public function stats(User $user): array
    {
        $this->featureService->ensureAffiliateEnabled();
        $profile = $this->ensureProfile($user);

        $stats = AffiliateStats::query()
            ->selectRaw('coalesce(sum(no_of_click), 0) as click, coalesce(sum(no_of_order_item), 0) as item, coalesce(sum(no_of_delivered), 0) as delivered, coalesce(sum(no_of_cancel), 0) as cancel')
            ->where('affiliate_user_id', $profile->id)
            ->first();

        return [
            'click' => (int) ($stats?->click ?? 0),
            'item' => (int) ($stats?->item ?? 0),
            'delivered' => (int) ($stats?->delivered ?? 0),
            'cancel' => (int) ($stats?->cancel ?? 0),
        ];
    }

    public function updatePaymentSettings(User $user, array $payload): array
    {
        $this->featureService->ensureAffiliateEnabled();

        $profile = $this->ensureProfile($user);
        $profile->paypal_email = $payload['paypalEmail'];
        $profile->bank_information = $payload['bankInformations'];
        $profile->save();

        return [
            'message' => 'Affiliate payment settings has been updated successfully',
            'status' => 200,
        ];
    }

    public function userCheck(User $user): array
    {
        $this->featureService->ensureAffiliateEnabled();

        if (!$user->referral_code) {
            $user->referral_code = strtoupper(Str::random(8));
            $user->save();
        }

        $affiliatedUser = $user->affiliate_user
            ? ((int) ($user->affiliate_user->status ?? 0) === 1)
            : false;

        $affiliateOption = false;

        if (Schema::hasTable('affiliate_options')) {
            $productSharing = AffiliateOption::query()->where('type', 'product_sharing')->value('status');
            $categoryWise = AffiliateOption::query()->where('type', 'category_wise_affiliate')->value('status');
            $affiliateOption = ((int) get_setting('affiliate_system') === 1) && ((bool) $productSharing || (bool) $categoryWise);
        }

        return [
            'affiliated_user' => $affiliatedUser,
            'user_referral_code' => $user->referral_code,
            'affiliate_option' => $affiliateOption,
            'status' => 200,
        ];
    }

    public function convertToWallet(User $user, float $amount): array
    {
        $this->featureService->ensureAffiliateEnabled();
        $profile = $this->ensureProfile($user);

        if ((float) $profile->balance < $amount) {
            return [
                'result' => false,
                'success' => false,
                'message' => translate("You can't request for convert more than your affiliate balance"),
            ];
        }

        DB::transaction(function () use ($user, $profile, $amount) {
            $profile->balance = (float) $profile->balance - $amount;
            $profile->save();

            $user->balance = (float) $user->balance + $amount;
            $user->save();

            AffiliatePayment::query()->create([
                'affiliate_user_id' => $profile->id,
                'amount' => $amount,
                'payment_method' => 'Converted To Wallet',
                'payment_details' => 'Converted To Wallet',
            ]);

            if (Schema::hasTable('wallets')) {
                Wallet::query()->create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'payment_method' => 'Converted To Wallet',
                    'payment_details' => 'Converted To Wallet',
                    'details' => 'Converted To Wallet',
                    'type' => 'Added',
                    'approval' => 1,
                ]);
            }
        });

        return [
            'result' => true,
            'success' => true,
            'data' => $amount,
            'message' => 'The amount is converted successfully!',
        ];
    }

    public function withdraw(User $user, float $amount): array
    {
        $this->featureService->ensureAffiliateEnabled();
        $profile = $this->ensureProfile($user);

        if ((float) $profile->balance < $amount) {
            return [
                'result' => false,
                'success' => false,
                'message' => translate("You can't request for withdraw more than your affiliate balance"),
            ];
        }

        AffiliateWithdrawRequest::query()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => 0,
        ]);

        return [
            'result' => true,
            'success' => true,
            'data' => $amount,
            'message' => 'Request submitted successfully! Please wait for approval',
        ];
    }

    public function withdrawHistory(User $user, int $perPage = 5): LengthAwarePaginator
    {
        $this->featureService->ensureAffiliateEnabled();

        return AffiliateWithdrawRequest::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function paymentHistory(User $user, int $perPage = 5): LengthAwarePaginator
    {
        $this->featureService->ensureAffiliateEnabled();
        $profile = $this->ensureProfile($user);

        return AffiliatePayment::query()
            ->where('affiliate_user_id', $profile->id)
            ->latest()
            ->paginate($perPage);
    }

    public function earningHistory(User $user, int $perPage = 5): LengthAwarePaginator
    {
        $this->featureService->ensureAffiliateEnabled();

        return AffiliateLog::query()
            ->with(['user', 'order_detail.product.product_translations', 'order_detail.order'])
            ->where('referred_by_user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    private function ensureProfile(User $user): AffiliateUser
    {
        $profile = AffiliateUser::query()->firstOrNew([
            'user_id' => $user->id,
        ]);

        if (!$profile->exists) {
            $profile->balance = 0;
            if (Schema::hasColumn('affiliate_users', 'status')) {
                $profile->status = 0;
            }
            $profile->save();
        }

        return $profile;
    }
}
