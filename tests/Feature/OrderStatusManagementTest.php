<?php

use App\Livewire\Admin\OrderStatus\OrderStatusCreatePage;
use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\User;
use App\Services\Order\OrderStatusService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

function makeOrderStatusAdmin(): Admin
{
    return Admin::create([
        'name' => 'Order Status Admin',
        'username' => 'statusadmin',
        'email' => 'statusadmin@example.com',
        'role' => Admin::ROLE_SUPER_ADMIN,
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ]);
}

function multilangName(string $en, ?string $ru = null, ?string $tg = null): array
{
    return [
        'en' => $en,
        'ru' => $ru ?? $en,
        'tg' => $tg ?? $en,
    ];
}

it('seeds default system order statuses with translations', function () {
    $paid = OrderStatus::query()->where('code', OrderStatus::CODE_PAID)->first();

    expect(OrderStatus::query()->where('is_system', true)->where('is_active', true)->count())->toBe(8)
        ->and($paid)->not->toBeNull()
        ->and($paid->getTranslation('name', 'en'))->toBe('Order Created')
        ->and($paid->getTranslation('name', 'ru'))->toBe('Заказ создан')
        ->and($paid->getTranslation('name', 'tg'))->toBe('Фармоиш эҷод шуд')
        ->and(OrderStatus::query()->where('code', OrderStatus::CODE_DELIVERED_TO_CUSTOMER)->exists())->toBeTrue();
});

it('allows admin to create custom order status', function () {
    $admin = makeOrderStatusAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(OrderStatusCreatePage::class)
        ->set('name.en', 'In Transit Custom')
        ->set('name.ru', 'В пути (свой)')
        ->set('name.tg', 'Дар роҳ (фармоишӣ)')
        ->set('code', 'in_transit_custom')
        ->set('color', 'primary')
        ->set('sortOrder', '35')
        ->set('isActive', true)
        ->call('save')
        ->assertRedirect(route('admin.order-statuses.index'));

    $status = OrderStatus::query()->where('code', 'in_transit_custom')->first();

    expect($status)->not->toBeNull()
        ->and($status->getTranslation('name', 'en'))->toBe('In Transit Custom')
        ->and($status->getTranslation('name', 'ru'))->toBe('В пути (свой)')
        ->and($status->getTranslation('name', 'tg'))->toBe('Дар роҳ (фармоишӣ)');
});

it('prevents deleting system order statuses', function () {
    $status = OrderStatus::query()->where('code', OrderStatus::CODE_PAID)->first();

    expect(fn () => app(OrderStatusService::class)->softDelete($status))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

it('soft deletes custom status and restores from trash', function () {
    $status = OrderStatus::create([
        'name' => multilangName('Custom'),
        'code' => 'custom_status',
        'color' => 'info',
        'sort_order' => 99,
        'is_system' => false,
        'is_active' => true,
    ]);

    app(OrderStatusService::class)->softDelete($status);

    expect(OrderStatus::query()->where('code', 'custom_status')->exists())->toBeFalse()
        ->and(OrderStatus::onlyTrashed()->where('code', 'custom_status')->exists())->toBeTrue();

    app(OrderStatusService::class)->restore($status->id);

    expect(OrderStatus::query()->where('code', 'custom_status')->exists())->toBeTrue();
});

it('prevents permanent delete when orders use the status', function () {
    $user = User::factory()->create();
    $status = OrderStatus::create([
        'name' => multilangName('Legacy'),
        'code' => 'legacy',
        'color' => 'secondary',
        'sort_order' => 200,
        'is_system' => false,
        'is_active' => true,
    ]);

    CustomerOrder::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'status' => 'legacy',
        'payment_status' => 'unpaid',
        'customer_total_cny' => 10,
    ]);

    app(OrderStatusService::class)->softDelete($status);

    expect(fn () => app(OrderStatusService::class)->forceDelete($status->id))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

it('listActive always returns order status models from database', function () {
    Cache::forever('order_statuses.active', serialize(new \stdClass()));

    $statuses = app(OrderStatusService::class)->listActive();

    expect($statuses)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($statuses)->not->toBeEmpty()
        ->and($statuses->first())->toBeInstanceOf(OrderStatus::class);
});

it('shows order status management page to admins', function () {
    $admin = makeOrderStatusAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.order-statuses.index'))
        ->assertOk()
        ->assertSee('Order Statuses')
        ->assertSee('Order Created');
});
