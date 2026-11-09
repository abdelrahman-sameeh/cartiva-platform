<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewWebController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function store(StoreReviewRequest $request): RedirectResponse
    {
        $result = $this->reviewService->createReview(
            $request->user(),
            $request->validated()
        );

        if (! $result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    public function productReviews(Request $request, Product $product): JsonResponse
    {
        $reviews = $product->reviews()
            ->with([
                'user' => fn ($query) => $query
                    ->without('roles')
                    ->select('id', 'first_name', 'last_name'),
            ])
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json(['reviews' => $reviews]);
    }

    public function specificUserReviews(Request $request): JsonResponse
    {
        $reviews = $request->user()
            ->reviews()
            ->with('product')
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json(['reviews' => $reviews]);
    }

    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        Gate::authorize('update', $review);
        $this->reviewService->updateReview($review, $request->validated());

        return back()->with('success', 'تم تعديل تقييمك بنجاح.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        Gate::authorize('delete', $review);
        $this->reviewService->deleteReview($review);

        return back()->with('success', 'تم حذف تقييمك بنجاح.');
    }

    private function perPage(Request $request): int
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        return (int) ($validated['per_page'] ?? 10);
    }
}
