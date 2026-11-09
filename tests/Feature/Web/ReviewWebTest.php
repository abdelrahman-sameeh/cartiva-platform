<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_review_a_product_from_a_completed_order(): void
    {
        $buyer = $this->createUser('reviewer@example.com');
        $product = $this->createProduct();
        $this->createCompletedPurchase($buyer, $product);

        $this->actingAs($buyer)
            ->post(route('reviews.store'), [
                'product_id' => $product->id,
                'rating' => 5,
                'comment' => 'منتج ممتاز ويستحق الشراء.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'منتج ممتاز ويستحق الشراء.',
        ]);
        $this->assertSame(1, $product->fresh()->rating_count);
        $this->assertSame('5.0', $product->fresh()->rating_avg);
    }

    public function test_buyer_cannot_review_without_a_completed_purchase_or_review_twice(): void
    {
        $buyer = $this->createUser('buyer@example.com');
        $product = $this->createProduct();

        $this->actingAs($buyer)
            ->post(route('reviews.store'), [
                'product_id' => $product->id,
                'rating' => 4,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('reviews', 0);

        $this->createCompletedPurchase($buyer, $product);

        $this->actingAs($buyer)
            ->post(route('reviews.store'), [
                'product_id' => $product->id,
                'rating' => 4,
            ])
            ->assertSessionHas('success');

        $this->actingAs($buyer)
            ->post(route('reviews.store'), [
                'product_id' => $product->id,
                'rating' => 2,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_owner_can_update_and_delete_a_review_and_product_rating_is_recalculated(): void
    {
        $owner = $this->createUser('owner@example.com');
        $otherBuyer = $this->createUser('other@example.com');
        $product = $this->createProduct();
        $review = $this->createReview($owner, $product, 5);
        $this->createReview($otherBuyer, $product, 1);
        $product->forceFill(['rating_avg' => 3, 'rating_count' => 2])->save();

        $this->actingAs($owner)
            ->patch(route('reviews.update', $review), [
                'rating' => 3,
                'comment' => 'تجربة جيدة بعد التعديل.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => 'تجربة جيدة بعد التعديل.',
        ]);
        $this->assertSame('2.0', $product->fresh()->rating_avg);
        $this->assertSame(2, $product->fresh()->rating_count);

        $this->actingAs($owner)
            ->delete(route('reviews.destroy', $review))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertSame('1.0', $product->fresh()->rating_avg);
        $this->assertSame(1, $product->fresh()->rating_count);
    }

    public function test_user_cannot_update_or_delete_another_users_review(): void
    {
        $owner = $this->createUser('owner@example.com');
        $otherBuyer = $this->createUser('other@example.com');
        $review = $this->createReview($owner, $this->createProduct(), 5);

        $this->actingAs($otherBuyer)
            ->patch(route('reviews.update', $review), [
                'rating' => 1,
                'comment' => 'غير مسموح',
            ])
            ->assertForbidden();

        $this->actingAs($otherBuyer)
            ->delete(route('reviews.destroy', $review))
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
        ]);
    }

    public function test_review_lists_are_paginated_and_the_personal_list_requires_authentication(): void
    {
        $buyer = $this->createUser('buyer@example.com');
        $product = $this->createProduct();
        $review = $this->createReview($buyer, $product, 4);

        $this->getJson(route('products.reviews.index', ['product' => $product, 'per_page' => 5]))
            ->assertOk()
            ->assertJsonPath('reviews.per_page', 5)
            ->assertJsonPath('reviews.data.0.id', $review->id)
            ->assertJsonPath('reviews.data.0.user.id', $buyer->id);

        $this->getJson(route('reviews.mine'))
            ->assertUnauthorized();

        $this->actingAs($buyer)
            ->getJson(route('reviews.mine'))
            ->assertOk()
            ->assertJsonPath('reviews.data.0.id', $review->id)
            ->assertJsonPath('reviews.data.0.product.id', $product->id);
    }

    private function createUser(string $email): User
    {
        return User::create([
            'first_name' => 'مستخدم',
            'last_name' => 'تجريبي',
            'email' => $email,
            'password' => 'password123',
        ]);
    }

    private function createProduct(): Product
    {
        $seller = $this->createUser(fake()->unique()->safeEmail());
        $seller->syncRoles(RoleName::SELLER);

        return Product::create([
            'title' => 'منتج التقييم',
            'description' => 'وصف المنتج المستخدم في اختبارات التقييمات.',
            'price' => 250,
            'discount_amount' => 0,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
    }

    private function createCompletedPurchase(User $buyer, Product $product): void
    {
        $order = $buyer->orders()->create([
            'subtotal' => 250,
            'discount' => 0,
            'total_price' => 250,
            'phone' => '01000000000',
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'card',
        ]);
        $subOrder = $order->subOrders()->create([
            'seller_id' => $product->seller_id,
            'subtotal' => 250,
            'discount' => 0,
            'total_price' => 250,
            'status' => 'completed',
        ]);
        $subOrder->items()->create([
            'product_id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'description' => $product->description,
            'price_at_purchase' => 250,
            'quantity' => 1,
        ]);
    }

    private function createReview(User $user, Product $product, int $rating): Review
    {
        return $user->reviews()->create([
            'product_id' => $product->id,
            'rating' => $rating,
            'comment' => 'تقييم للاختبار.',
        ]);
    }
}
