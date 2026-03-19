<?php

namespace App\Http\Controllers\Api\V1\Benefits;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Benefits\AffiliateAmountRequest;
use App\Http\Requests\Api\V1\Benefits\AffiliatePaymentSettingsRequest;
use App\Http\Requests\Api\V1\Benefits\RegisterAffiliateRequest;
use App\Http\Resources\Api\V1\Benefits\AffiliateEarningHistoryResource;
use App\Http\Resources\Api\V1\Benefits\AffiliatePaymentHistoryResource;
use App\Http\Resources\Api\V1\Benefits\AffiliateStatResource;
use App\Http\Resources\Api\V1\Benefits\AffiliateWithdrawRequestResource;
use App\Services\Benefits\AffiliateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AffiliateController extends Controller
{
    public function __construct(private readonly AffiliateService $affiliateService)
    {
    }

    public function store(RegisterAffiliateRequest $request): JsonResponse
    {
        return response()->json(
            $this->affiliateService->register($request->user(), $request->validated())
        );
    }

    public function affiliateBalance(Request $request): JsonResponse
    {
        return response()->json([
            'affiliate_balance' => $this->affiliateService->balance($request->user()),
            'status' => 200,
        ]);
    }

    public function referralCode(Request $request): JsonResponse
    {
        return response()->json([
            'referral_code' => $this->affiliateService->referralUrl($request->user()),
            'status' => 200,
        ]);
    }

    public function affiliateStats(Request $request): JsonResponse
    {
        return response()->json([
            'data' => (new AffiliateStatResource($this->affiliateService->stats($request->user())))->resolve(),
        ]);
    }

    public function paymentSettings(AffiliatePaymentSettingsRequest $request): JsonResponse
    {
        return response()->json(
            $this->affiliateService->updatePaymentSettings($request->user(), $request->validated())
        );
    }

    public function affiliateUserCheck(Request $request): JsonResponse
    {
        return response()->json($this->affiliateService->userCheck($request->user()));
    }

    public function affiliateAmountConvertToWallet(AffiliateAmountRequest $request): JsonResponse
    {
        return response()->json(
            $this->affiliateService->convertToWallet($request->user(), (float) $request->validated('amount'))
        );
    }

    public function withdrawRequestStore(AffiliateAmountRequest $request): JsonResponse
    {
        return response()->json(
            $this->affiliateService->withdraw($request->user(), (float) $request->validated('amount'))
        );
    }

    public function withdrawRequestList(Request $request): JsonResponse
    {
        try {
            $history = $this->affiliateService->withdrawHistory($request->user());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
                'status' => $exception->getStatusCode(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'data' => AffiliateWithdrawRequestResource::collection($history->items())->resolve(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    public function paymentHistory(Request $request): JsonResponse
    {
        try {
            $history = $this->affiliateService->paymentHistory($request->user());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
                'status' => $exception->getStatusCode(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'data' => AffiliatePaymentHistoryResource::collection($history->items())->resolve(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    public function earningHistory(Request $request): JsonResponse
    {
        try {
            $history = $this->affiliateService->earningHistory($request->user());
        } catch (HttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => translate($exception->getMessage()),
                'status' => $exception->getStatusCode(),
            ], $exception->getStatusCode());
        }

        return response()->json([
            'data' => AffiliateEarningHistoryResource::collection($history->items())->resolve(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }
}
