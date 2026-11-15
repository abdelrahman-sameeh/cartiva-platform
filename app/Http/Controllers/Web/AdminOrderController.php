<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateSubOrderStatusRequest;
use App\Models\Order\SubOrder;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(): View
    {
        return view('admin.orders.index', [
            'subOrders' => SubOrder::query()
                ->with(['order.user:id,first_name,last_name,email', 'seller:id,first_name,last_name,email'])
                ->withCount('items')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(SubOrder $subOrder): View
    {
        $subOrder->load([
            'order.user:id,first_name,last_name,email',
            'order.address',
            'seller:id,first_name,last_name,email',
            'items.pictures',
        ]);

        return view('seller.orders.show', [
            'subOrder' => $subOrder,
            'allowedStatuses' => $this->orderService->allowedStatusTransitions($subOrder),
            'managementContext' => 'admin',
        ]);
    }

    public function updateStatus(
        UpdateSubOrderStatusRequest $request,
        SubOrder $subOrder
    ): RedirectResponse {
        $this->orderService->updateSubOrderStatusAsAdmin(
            $subOrder,
            $request->validated('status')
        );

        return back()->with('success', 'تم تحديث حالة الطلب بنجاح.');
    }
}
