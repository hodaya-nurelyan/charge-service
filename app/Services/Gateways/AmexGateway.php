<?php
namespace App\Services\Gateways;

use App\Services\Contracts\PaymentGatewayInterface;
use App\Services\PaymentResult;
use App\Services\GatewayException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class AmexGateway implements PaymentGatewayInterface
{
    protected string $base;
    protected string $key;
    protected string $providerName = 'GatewayA';

    public function __construct()
    {
        $this->base = config('payments.a.base');
        $this->key = config('payments.a.key');
    }

    public function charge(array $data): PaymentResult
    {
        $url = rtrim($this->base, '/') . '/v1/charges';

        $payload = [
            'amount_cents' => (int) round($data['price'] * 100),
            'currency' => $data['currency'],
            'meta' => ['ride_id' => $data['ride_id']],
            'network' => 'AMEX',
            'card_token' => $data['card_token'],
        ];

        $attempts = 2;
        $sleep = 100000; // microseconds for usleep: 100ms

        for ($attempt = 0; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::withToken($this->key)
                    ->timeout(2.5)
                    ->post($url, $payload);

                if ($response->serverError()) {
                    throw new \Exception('server_error');
                }

                if ($response->clientError()) {
                    // Decline or invalid
                    $body = $response->json();
                    $code = $body['code'] ?? null;
                    $message = $body['message'] ?? null;
                    return PaymentResult::declined($this->providerName, $code, $message);
                }

                $body = $response->json();
                return PaymentResult::approved($this->providerName, $body['id'] ?? null, $body['processed_at'] ?? null);
            } catch (ConnectionException $e) {
                // treat as transient timeout
                if ($attempt < $attempts) {
                    usleep($sleep);
                    $sleep *= 2;
                    continue;
                }
                throw new GatewayException('Gateway timeout', $this->providerName, true);
            } catch (\Exception $e) {
                if ($attempt < $attempts) {
                    usleep($sleep);
                    $sleep *= 2;
                    continue;
                }
                throw new GatewayException('Gateway unavailable', $this->providerName, true);
            }
        }

        throw new GatewayException('Gateway unavailable', $this->providerName, true);
    }
}
