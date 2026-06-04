<?php

namespace Tests\Unit\Pengingat;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_bisa_membuat_subscription_milik_user(): void
    {
        $user = User::factory()->create();

        $sub = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.example/abc',
            'public_key' => 'p256dh-key',
            'auth_token' => 'auth-key',
            'user_agent' => 'Chrome',
        ]);

        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://push.example/abc']);
        $this->assertSame($user->id, $sub->user->id);
    }
}
