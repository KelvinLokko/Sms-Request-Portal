<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCompanies;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesCompanies;
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->withoutVite()->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_company_owners_see_company_overview_stats(): void
    {
        [$user] = $this->createApprovedCompanyOwner();

        $this->actingAs($user)
            ->withoutVite()
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('isStaff', false)
                ->has('companyOverview.stats.drafts')
                ->has('companyOverview.stats.in_flight')
                ->has('companyOverview.stats.fulfilled')
                ->has('companyOverview.stats.open_invoices')
                ->has('companyOverview.recent_campaigns')
                ->has('companyOverview.recent_invoices')
                ->has('companyOverview.sender_ids')
                ->where('summary', null));
    }
}
