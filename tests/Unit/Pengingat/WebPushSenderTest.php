<?php

namespace Tests\Unit\Pengingat;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPush\WebPushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_hapus_subscription_kedaluwarsa_berdasar_endpoint(): void
    {
        $u = User::factory()->create();
        PushSubscription::create(['user_id' => $u->id, 'endpoint' => 'https://a', 'public_key' => 'p', 'auth_token' => 'a']);
        PushSubscription::create(['user_id' => $u->id, 'endpoint' => 'https://b', 'public_key' => 'p', 'auth_token' => 'a']);

        (new WebPushSender())->hapusSubscriptionKedaluwarsa(['https://a']);

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => 'https://a']);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://b']);
    }

    public function test_kirim_ke_user_tanpa_subscription_tidak_error_return_nol(): void
    {
        $u = User::factory()->create();
        $terkirim = (new WebPushSender())->kirimKeUser($u->id, ['judul' => 'Hai', 'isi' => 'tes', 'url' => '/']);
        $this->assertSame(0, $terkirim);
    }
}
