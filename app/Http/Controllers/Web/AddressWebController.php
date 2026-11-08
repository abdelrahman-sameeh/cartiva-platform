<?php

namespace App\Http\Controllers\Web;

use App\Helper\Countries;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Models\Address;
use App\Services\AddressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddressWebController extends Controller
{
    public function __construct(private readonly AddressService $addressService) {}

    public function index(Request $request): View
    {
        return view('addresses.index', [
            'addresses' => $this->addressService->forUser($request->user())->latest()->get(),
            'countries' => Countries::LIST,
        ]);
    }

    public function create(): View
    {
        return view('addresses.create', ['countries' => Countries::LIST]);
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $this->addressService->create($request->user(), $request->validated());

        return redirect()->route('addresses.index')->with('success', 'تمت إضافة العنوان بنجاح.');
    }

    public function edit(Request $request, Address $address): View
    {
        return view('addresses.edit', [
            'address' => $this->addressService->ownedBy($request->user(), $address),
            'countries' => Countries::LIST,
        ]);
    }

    public function update(UpdateAddressRequest $request, Address $address): RedirectResponse
    {
        $this->addressService->update($request->user(), $address, $request->validated());

        return redirect()->route('addresses.index')->with('success', 'تم تعديل العنوان بنجاح.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        if (! $this->addressService->delete($request->user(), $address)) {
            return back()->withErrors(['address' => 'لا يمكن حذف العنوان الافتراضي.']);
        }

        return redirect()->route('addresses.index')->with('success', 'تم حذف العنوان بنجاح.');
    }
}
