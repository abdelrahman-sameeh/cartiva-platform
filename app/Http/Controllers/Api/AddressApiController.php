<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Models\Address;
use App\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AddressApiController extends Controller
{
    public function __construct(private readonly AddressService $addressService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->addressService->forUser($request->user())->latest()->get()
        );
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $this->addressService->create($request->user(), $request->validated());

        return response()->json($address, 201);
    }

    public function show(Request $request, Address $address): JsonResponse
    {
        return response()->json($this->addressService->ownedBy($request->user(), $address));
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        return response()->json(
            $this->addressService->update($request->user(), $address, $request->validated())
        );
    }

    public function destroy(Request $request, Address $address): JsonResponse|Response
    {
        if (! $this->addressService->delete($request->user(), $address)) {
            return response()->json(['message' => "can't delete default address"], 400);
        }

        return response()->noContent();
    }
}
