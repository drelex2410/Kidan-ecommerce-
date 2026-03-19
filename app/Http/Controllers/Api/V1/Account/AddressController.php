<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\StoreAddressRequest;
use App\Http\Requests\Api\V1\Account\UpdateAddressRequest;
use App\Http\Resources\Api\V1\Account\AddressResource;
use App\Services\Account\AddressBookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AddressController extends Controller
{
    public function __construct(private readonly AddressBookService $addressBookService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => AddressResource::collection($this->addressBookService->listForUser($request->user()))->resolve(),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $this->addressBookService->create($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => translate('Address has been added successfully.'),
            'data' => (new AddressResource($address))->resolve(),
        ]);
    }

    public function update(UpdateAddressRequest $request): JsonResponse
    {
        try {
            $addresses = $this->addressBookService->update($request->user(), $request->validated());
        } catch (AccessDeniedHttpException) {
            return response()->json(null, 401);
        }

        return response()->json([
            'success' => true,
            'message' => translate('Address has been updated successfully.'),
            'data' => AddressResource::collection($addresses)->resolve(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $addresses = $this->addressBookService->delete($request->user(), $id);
        } catch (AccessDeniedHttpException) {
            return response()->json(null, 401);
        }

        return response()->json([
            'success' => true,
            'message' => translate('Address has been deleted successfully.'),
            'data' => AddressResource::collection($addresses)->resolve(),
        ]);
    }

    public function defaultShipping(Request $request, int $id): JsonResponse
    {
        try {
            $addresses = $this->addressBookService->markDefaultShipping($request->user(), $id);
        } catch (AccessDeniedHttpException) {
            return response()->json(null, 401);
        }

        return response()->json([
            'success' => true,
            'message' => translate('Address has been marked as default shipping address.'),
            'data' => AddressResource::collection($addresses)->resolve(),
        ]);
    }

    public function defaultBilling(Request $request, int $id): JsonResponse
    {
        try {
            $addresses = $this->addressBookService->markDefaultBilling($request->user(), $id);
        } catch (AccessDeniedHttpException) {
            return response()->json(null, 401);
        }

        return response()->json([
            'success' => true,
            'message' => translate('Address has been marked as default billing address.'),
            'data' => AddressResource::collection($addresses)->resolve(),
        ]);
    }
}
