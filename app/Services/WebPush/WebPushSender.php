<?php

namespace App\Services\WebPush;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushSender
{
    private ?WebPush $webPush = null;

    private function client(): WebPush
    {
        if ($this->webPush === null) {
            $this->webPush = new WebPush([
                'VAPID' => [
                    'subject'    => config('pengingat.vapid.subject'),
                    'publicKey'  => config('pengingat.vapid.public_key'),
                    'privateKey' => config('pengingat.vapid.private_key'),
                ],
            ]);
        }

        return $this->webPush;
    }

    /**
     * Kirim payload ke seluruh subscription milik user.
     * @param array{judul:string,isi:string,url:string} $payload
     * @return int jumlah notifikasi yang dikirim (di-queue)
     */
    public function kirimKeUser(string $userId, array $payload): int
    {
        $subs = PushSubscription::where('user_id', $userId)->get();
        if ($subs->isEmpty()) {
            return 0;
        }

        $client = $this->client();
        $body = json_encode([
            'title' => $payload['judul'],
            'body'  => $payload['isi'],
            'url'   => $payload['url'],
        ]);

        foreach ($subs as $sub) {
            $client->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys'     => ['p256dh' => $sub->public_key, 'auth' => $sub->auth_token],
                ]),
                $body,
            );
        }

        $endpointKedaluwarsa = [];
        foreach ($client->flush() as $report) {
            if (! $report->isSuccess() && $report->isSubscriptionExpired()) {
                $endpointKedaluwarsa[] = $report->getEndpoint();
            }
        }

        if ($endpointKedaluwarsa !== []) {
            $this->hapusSubscriptionKedaluwarsa($endpointKedaluwarsa);
        }

        return $subs->count();
    }

    public function hapusSubscriptionKedaluwarsa(array $endpoints): void
    {
        if ($endpoints === []) {
            return;
        }
        PushSubscription::whereIn('endpoint', $endpoints)->delete();
    }
}
