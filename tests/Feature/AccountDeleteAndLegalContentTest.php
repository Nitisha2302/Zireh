<?php

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PrivacyTermsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

it('deletes the authenticated customer account and tokens', function () {
    $user = User::factory()->create([
        'name' => 'Delete Me',
        'phone' => '992900000001',
    ]);

    $token = $user->createToken('test-device')->plainTextToken;
    $tokenId = (int) explode('|', $token)[0];

    $this->withToken($token)
        ->deleteJson('/api/v1/auth/account')
        ->assertOk()
        ->assertJsonPath('message', __('api.account_deleted'));

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse()
        ->and(PersonalAccessToken::query()->whereKey($tokenId)->exists())->toBeFalse();
});

it('rejects unauthenticated account deletion', function () {
    $this->deleteJson('/api/v1/auth/account')
        ->assertUnauthorized();
});

it('returns legal content from settings via public api', function () {
    $this->seed(PrivacyTermsSeeder::class);

    $this->getJson('/api/v1/legal')
        ->assertOk()
        ->assertJsonPath('message', __('api.legal_content_fetched'))
        ->assertJsonPath('data.privacy_policy', Setting::query()->where('key', 'privacy_policy')->value('value'))
        ->assertJsonPath('data.terms_conditions', Setting::query()->where('key', 'terms_conditions')->value('value'))
        ->assertJsonPath('data.delete_account', Setting::query()->where('key', 'delete_account')->value('value'));

    $this->getJson('/api/v1/public/legal')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'privacy_policy',
                'terms_conditions',
                'delete_account',
            ],
        ]);

    expect($this->getJson('/api/v1/legal')->json('data.privacy_policy'))->toContain('ZirehCargo')
        ->and($this->getJson('/api/v1/legal')->json('data.terms_conditions'))->toContain('ZirehCargo');
});

it('seeds zireh cargo privacy terms and delete account settings', function () {
    $this->seed(PrivacyTermsSeeder::class);

    expect(Setting::query()->where('key', 'privacy_policy')->value('value'))->toContain('ZirehCargo Privacy Policy')
        ->and(Setting::query()->where('key', 'terms_conditions')->value('value'))->toContain('ZirehCargo Terms')
        ->and(Setting::query()->where('key', 'delete_account')->value('value'))->toContain('Delete Your ZirehCargo Account');
});
