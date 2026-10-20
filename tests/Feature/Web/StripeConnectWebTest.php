<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\SellerStripeAccount;
use App\Models\User;
use App\Services\Sellers\SellerOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StripeConnectWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.secret' => 'sk_test_example']);
    }

    public function test_only_sellers_can_open_stripe_connect_settings(): void
    {
        $this->get(route('seller.stripe-connect.show'))
            ->assertRedirect(route('login'));

        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('seller.stripe-connect.show'))
            ->assertForbidden();

        $seller = $this->createSeller();

        $this->actingAs($seller)
            ->get(route('seller.stripe-connect.show'))
            ->assertOk()
            ->assertSee('لم تبدأ إعداد حساب المدفوعات بعد')
            ->assertSee('بدء الإعداد في Stripe');
    }

    public function test_seller_dashboard_links_to_stripe_connect_settings(): void
    {
        $seller = $this->createSeller();

        $this->actingAs($seller)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('seller.stripe-connect.show'), false)
            ->assertSee('إعداد المدفوعات');
    }

    public function test_ready_account_status_is_shown_without_an_onboarding_button(): void
    {
        $seller = $this->createSeller();
        $seller->stripeAccount()->create([
            'stripe_account_id' => 'acct_ready',
            'card_payments_status' => 'active',
            'payouts_status' => 'active',
            'transfers_status' => 'active',
            'requirements' => [],
            'last_synced_at' => now(),
        ]);

        $this->actingAs($seller)
            ->get(route('seller.stripe-connect.show'))
            ->assertOk()
            ->assertSee('حسابك جاهز لاستقبال المدفوعات والتحويلات')
            ->assertDontSee('استكمال الإعداد في Stripe');
    }

    public function test_outstanding_requirements_are_grouped_without_exposing_stripe_field_names(): void
    {
        $seller = $this->createSeller();
        $seller->stripeAccount()->create([
            'stripe_account_id' => 'acct_restricted',
            'requirements' => [[
                'description' => 'configuration.merchant.mcc',
                'awaiting_action_from' => 'user',
                'deadline_status' => 'past_due',
            ]],
        ]);

        $this->actingAs($seller)
            ->get(route('seller.stripe-connect.show'))
            ->assertOk()
            ->assertSee('خطوات بسيطة متبقية')
            ->assertSee('البيانات المطلوبة حاليًا فقط')
            ->assertDontSee('configuration.merchant.mcc');
    }

    public function test_seller_can_start_and_refresh_onboarding(): void
    {
        $seller = $this->createSeller();
        $service = Mockery::mock(SellerOnboardingService::class);
        $service->shouldReceive('onboardingUrl')
            ->twice()
            ->with(
                Mockery::on(fn (User $user) => $user->is($seller)),
                route('seller.stripe-connect.refresh'),
                route('seller.stripe-connect.return')
            )
            ->andReturn('https://connect.stripe.test/onboarding');
        $this->app->instance(SellerOnboardingService::class, $service);

        $this->actingAs($seller)
            ->post(route('seller.stripe-connect.onboard'))
            ->assertRedirect('https://connect.stripe.test/onboarding');

        $this->actingAs($seller)
            ->get(route('seller.stripe-connect.refresh'))
            ->assertRedirect('https://connect.stripe.test/onboarding');
    }

    public function test_return_from_stripe_synchronizes_the_sellers_account(): void
    {
        $seller = $this->createSeller();
        $stripeAccount = $seller->stripeAccount()->create([
            'stripe_account_id' => 'acct_seller',
        ]);
        $service = Mockery::mock(SellerOnboardingService::class);
        $service->shouldReceive('sync')
            ->once()
            ->with(Mockery::on(fn (SellerStripeAccount $account) => $account->is($stripeAccount)))
            ->andReturn($stripeAccount);
        $this->app->instance(SellerOnboardingService::class, $service);

        $this->actingAs($seller)
            ->get(route('seller.stripe-connect.return'))
            ->assertRedirect(route('seller.stripe-connect.show'))
            ->assertSessionHas('success');
    }

    public function test_return_without_a_local_stripe_account_shows_an_error(): void
    {
        $seller = $this->createSeller();
        $service = Mockery::mock(SellerOnboardingService::class);
        $service->shouldReceive('sync')->never();
        $this->app->instance(SellerOnboardingService::class, $service);

        $this->actingAs($seller)
            ->get(route('seller.stripe-connect.return'))
            ->assertRedirect(route('seller.stripe-connect.show'))
            ->assertSessionHasErrors('stripe');
    }

    private function createSeller(): User
    {
        $seller = User::factory()->create();
        $seller->syncRoles(RoleName::SELLER);

        return $seller;
    }
}
