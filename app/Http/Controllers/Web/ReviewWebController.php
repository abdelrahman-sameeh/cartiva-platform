<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\StoreReviewRequest;
use App\Services\ReviewService;

class ReviewWebController
{
    public function __construct(private ReviewService $reviewService) {}

    public function store(StoreReviewRequest $request)
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
}
