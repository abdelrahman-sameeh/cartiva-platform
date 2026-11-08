<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateSubOrderStatusRequest;
use App\Models\Order\Order;
use App\Models\Order\SubOrder;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderWebController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly CartService $cartService
    ) {}

    public function index(Request $request): View
    {
        return view('orders.index', [
            'orders' => $this->orderService
                ->forCustomer($request->user())
                ->withCount('subOrders')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $cart = $this->cartService->data($this->cartService->get($request->user()));

        if ($cart['items_count'] === 0) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'السلة فارغة.']);
        }

        return view('orders.create', [
            'cart' => $cart,
            'addresses' => $request->user()->addresses()
                ->orderByDesc('is_default')
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->orderService->create($request->user(), $request->validated());

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'تم إنشاء طلبك بنجاح.');
    }

    public function show(Request $request, Order $order): View
    {
        return view('orders.show', [
            'order' => $this->orderService->customerOrder($request->user(), $order),
        ]);
    }

    public function sellerIndex(Request $request): View
    {
        return view('seller.orders.index', [
            'subOrders' => $this->orderService
                ->forSeller($request->user())
                ->with(['order.user:id,first_name,last_name,email', 'items'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function sellerShow(Request $request, SubOrder $subOrder): View
    {
        return view('seller.orders.show', [
            'subOrder' => $this->orderService->sellerOrder($request->user(), $subOrder),
        ]);
    }

    public function updateSubOrderStatus(
        UpdateSubOrderStatusRequest $request,
        SubOrder $subOrder
    ): RedirectResponse {
        $this->orderService->updateSubOrderStatus(
            $request->user(),
            $subOrder,
            $request->validated('status')
        );

        return back()->with('success', 'تم تحديث حالة الطلب بنجاح.');
    }
}
