<?php

use App\Livewire\Authenticate\LoginPage;
use App\Models\Admin;
use App\Models\LoginLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeAdmin(array $attributes = []): Admin
{
    return Admin::create(array_merge([
        'name' => 'Super Admin',
        'username' => 'superadmin',
        'email' => 'admin@example.com',
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ], $attributes));
}

it('allows an admin to login with username and records the login log', function () {
    makeAdmin();

    Livewire::test(LoginPage::class)
        ->set('login', 'superadmin')
        ->set('password', 'secret-password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth()->guard('admin')->check())->toBeTrue();

    $log = LoginLog::query()->first();

    expect($log)
        ->guard->toBe('admin')
        ->login->toBe('superadmin')
        ->successful->toBeTrue()
        ->logout_at->toBeNull();
});

it('allows an admin to login with email', function () {
    makeAdmin();

    Livewire::test(LoginPage::class)
        ->set('login', 'admin@example.com')
        ->set('password', 'secret-password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth()->guard('admin')->check())->toBeTrue()
        ->and(LoginLog::query()->where('login', 'admin@example.com')->where('successful', true)->exists())->toBeTrue();
});

it('records failed admin login attempts', function () {
    makeAdmin();

    Livewire::test(LoginPage::class)
        ->set('login', 'superadmin')
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors(['login']);

    expect(auth()->guard('admin')->check())->toBeFalse()
        ->and(LoginLog::query()->where('login', 'superadmin')->where('successful', false)->exists())->toBeTrue();
});

it('shows the profile page to authenticated admins', function () {
    $admin = makeAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.profile'))
        ->assertOk()
        ->assertSee('My Profile')
        ->assertSee('Recent Login Logs');
});
