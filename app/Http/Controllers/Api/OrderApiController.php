<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateSubOrderStatusRequest;
use App\Models\Order\Order;
use App\Models\Order\SubOrder;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService
            ->forCustomer($request->user())
            ->with(['subOrders.items.pictures'])
            ->latest()
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        return response()->json([
            'order' => $this->orderService->customerOrder($request->user(), $order),
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'تم إنشاء الطلب بنجاح.',
            'order' => $order,
        ], 201);
    }

    public function sellerOrders(Request $request): JsonResponse
    {
        $subOrders = $this->orderService
            ->forSeller($request->user())
            ->with(['order:id,user_id,phone,address_id,payment_method,payment_status', 'items.pictures'])
            ->latest()
            ->get();

        return response()->json(['sub_orders' => $subOrders]);
    }

    public function showSellerOrder(Request $request, SubOrder $subOrder): JsonResponse
    {
        return response()->json([
            'sub_order' => $this->orderService->sellerOrder($request->user(), $subOrder),
        ]);
    }

    public function updateSubOrderStatus(
        UpdateSubOrderStatusRequest $request,
        SubOrder $subOrder
    ): JsonResponse {
        $subOrder = $this->orderService->updateSubOrderStatus(
            $request->user(),
            $subOrder,
            $request->validated('status')
        );

        return response()->json([
            'message' => 'تم تحديث حالة الطلب بنجاح.',
            'sub_order' => $subOrder,
        ]);
    }
}
