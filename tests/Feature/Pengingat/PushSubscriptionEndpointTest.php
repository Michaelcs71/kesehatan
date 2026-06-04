<?php

namespace Tests\Feature\Pengingat;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_login_bisa_subscribe(): void
    {
        $user = User::factory()->create();

        $resp = $this->actingAs($user)->postJson('/push/subscribe', [
            'endpoint' => 'https://push.example/xyz',
            'keys' => ['p256dh' => 'kunci-publik', 'auth' => 'kunci-auth'],
        ]);

        $resp->assertOk();
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id, 'endpoint' => 'https://push.example/xyz',
        ]);
    }

    public function test_subscribe_idempoten_per_endpoint(): void
    {
        $user = User::factory()->create();
        $payload = ['endpoint' => 'https://push.example/xyz', 'keys' => ['p256dh' => 'a', 'auth' => 'b']];

        $this->actingAs($user)->postJson('/push/subscribe', $payload)->assertOk();
        $this->actingAs($user)->postJson('/push/subscribe', $payload)->assertOk();

        $this->assertSame(1, PushSubscription::where('endpoint', 'https://push.example/xyz')->count());
    }

    public function test_bisa_unsubscribe(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/push/subscribe', [
            'endpoint' => 'https://push.example/xyz', 'keys' => ['p256dh' => 'a', 'auth' => 'b'],
        ])->assertOk();

        $this->actingAs($user)->deleteJson('/push/unsubscribe', ['endpoint' => 'https://push.example/xyz'])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => 'https://push.example/xyz']);
    }

    public function test_guest_ditolak(): void
    {
        $this->postJson('/push/subscribe', [])->assertUnauthorized();
    }
}
