<?php

namespace Tests\Feature\Web;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerFeeCalculatorWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.fees.platform_commission_bps' => 500,
            'services.stripe.fees.estimated_processing_bps' => 290,
            'services.stripe.fees.estimated_currency_conversion_bps' => 100,
            'services.stripe.fees.estimated_international_bps' => 150,
            'services.stripe.fees.estimated_fixed_usd_minor' => 30,
            'services.stripe.fees.estimated_usd_egp_rate' => 56,
        ]);
    }

    public function test_only_sellers_can_open_the_profit_calculator(): void
    {
        $this->get(route('seller.fee-calculator'))
            ->assertRedirect(route('login'));

        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('seller.fee-calculator'))
            ->assertForbidden();

        $seller = $this->createSeller();

        $this->actingAs($seller)
            ->get(route('seller.fee-calculator'))
            ->assertOk()
            ->assertSee('اعرف صافي أرباحك قبل إضافة المنتج')
            ->assertSee(route('seller.products.create'), false);
    }

    public function test_it_estimates_stripe_platform_fees_and_seller_profit(): void
    {
        $seller = $this->createSeller();

        $this->actingAs($seller)
            ->get(route('seller.fee-calculator', [
                'price' => '1000.00',
                'cost' => '600.00',
                'quantity' => 1,
                'usd_egp_rate' => '56.00',
            ]))
            ->assertOk()
            ->assertSee('55.80 جنيه')
            ->assertSee('50.00 جنيه')
            ->assertSee('894.20 جنيه')
            ->assertSee('294.20 جنيه');
    }

    public function test_international_card_estimate_adds_the_configured_fee(): void
    {
        $seller = $this->createSeller();

        $this->actingAs($seller)
            ->get(route('seller.fee-calculator', [
                'price' => '1000.00',
                'cost' => '0',
                'quantity' => 1,
                'usd_egp_rate' => '56.00',
                'international_card' => 1,
            ]))
            ->assertOk()
            ->assertSee('70.80 جنيه')
            ->assertSee('879.20 جنيه');
    }

    public function test_seller_can_calculate_net_payout_without_entering_product_cost(): void
    {
        $seller = $this->createSeller();

        $this->actingAs($seller)
            ->get(route('seller.fee-calculator', [
                'price' => '1000.00',
                'quantity' => 1,
                'usd_egp_rate' => '56.00',
            ]))
            ->assertOk()
            ->assertSee('صافي المبلغ المتوقع استلامه')
            ->assertSee('894.20 جنيه')
            ->assertSee('أضف تكلفة المنتج لو عايز تعرف ربحك الفعلي')
            ->assertDontSee('إجمالي تكلفة المنتجات')
            ->assertDontSee('هامش الربح');
    }

    public function test_calculator_inputs_are_validated(): void
    {
        $seller = $this->createSeller();

        $this->actingAs($seller)
            ->get(route('seller.fee-calculator', [
                'price' => '-1',
                'cost' => '0',
                'quantity' => 0,
                'usd_egp_rate' => '0',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['price', 'quantity', 'usd_egp_rate']);
    }

    public function test_seller_dashboard_links_to_the_profit_calculator(): void
    {
        $seller = $this->createSeller();

        $this->actingAs($seller)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('seller.fee-calculator'), false)
            ->assertSee('حاسبة الأرباح');
    }

    private function createSeller(): User
    {
        $seller = User::factory()->create();
        $seller->syncRoles(RoleName::SELLER);

        return $seller;
    }
}
