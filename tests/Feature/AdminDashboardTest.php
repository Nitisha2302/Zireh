<?php

use App\Models\Admin;
use App\Models\CurrencyExchangeRate;
use App\Models\CustomerOrder;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\Admin\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function makeDashboardAdmin(): Admin
{
    return Admin::create([
        'name' => 'Dashboard Admin',
        'username' => 'dashboardadmin',
        'email' => 'dashboard@example.com',
        'role' => Admin::ROLE_SUPER_ADMIN,
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ]);
}

it('shows dashboard overview to authenticated admins', function () {
    $admin = makeDashboardAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Welcome back, Dashboard Admin')
        ->assertSee('Customers')
        ->assertSee('Recent Orders');
});

it('aggregates dashboard metrics from database records', function () {
    User::factory()->create(['status' => User::STATUS_ACTIVE]);
    User::factory()->create(['status' => User::STATUS_INACTIVE]);

    CustomerOrder::create([
        'user_id' => User::query()->first()->id,
        'platform' => 'taobao',
        'status' => \App\Models\OrderStatus::CODE_DELIVERED_TO_CUSTOMER,
        'payment_status' => 'paid',
        'goods_subtotal_cny' => 100,
        'shipping_fee_cny' => 0,
        'elim_service_fee_cny' => 0,
        'commission_amount' => 10,
        'commission_percentage' => 10,
        'customer_total_cny' => 110,
        'exchange_rate' => 2,
        'customer_total_tjs' => 220,
        'final_amount_tjs' => 220,
    ]);

    UserWallet::create([
        'user_id' => User::query()->first()->id,
        'balance' => 50,
        'currency' => 'CNY',
    ]);

    CurrencyExchangeRate::create([
        'from_currency' => 'CNY',
        'to_currency' => 'TJS',
        'exchange_rate' => 2,
        'auto_refresh_enabled' => true,
        'refresh_interval_hours' => 1,
        'last_synced_at' => now(),
    ]);

    $overview = app(DashboardService::class)->overview();

    expect($overview['stats']['customers_total'])->toBe(2)
        ->and($overview['stats']['customers_active'])->toBe(1)
        ->and($overview['stats']['orders_total'])->toBe(1)
        ->and($overview['stats']['orders_completed'])->toBe(1)
        ->and($overview['stats']['revenue_cny_total'])->toBe(110.0)
        ->and($overview['stats']['revenue_tjs_total'])->toBe(220.0)
        ->and($overview['stats']['wallet_balance_total'])->toBe(50.0)
        ->and($overview['stats']['exchange_rate'])->toBe(2.0)
        ->and($overview['recent_orders'])->toHaveCount(1);
});

it('redirects guests away from dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect();
});
