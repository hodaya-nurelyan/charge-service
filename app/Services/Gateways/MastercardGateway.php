<?php
namespace App\Services\Gateways;

use App\Services\Contracts\PaymentGatewayInterface;
use App\Services\PaymentResult;
use App\Services\GatewayException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class MastercardGateway implements PaymentGatewayInterface
{
    protected string $base;
    protected string $key;
    protected string $providerName = 'GatewayC';

    public function __construct()
    {
        $this->base = config('payments.c.base');
        $this->key = config('payments.c.key');
    }

    public function charge(array $data): PaymentResult
    {
        $url = rtrim($this->base, '/') . '/payments/create';

        $payload = [
            'amount' => ['value' => number_format($data['price'], 2, '.', ''), 'currency' => $data['currency']],
            'metadata' => ['ride' => $data['ride_id']],
            'card_network_hint' => 'MASTERCARD',
            'card_token' => $data['card_token'],
        ];

        $auth = base64_encode($this->key . ':');

        $attempts = 2;
        $sleep = 100000;

        for ($attempt = 0; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::withHeaders(['Authorization' => 'Basic ' . $auth])
                    ->timeout(2.5)
                    ->post($url, $payload);

                if ($response->serverError()) {
                    throw new \Exception('server_error');
                }

                if ($response->clientError()) {
                    $body = $response->json();
                    $result = $body['result'] ?? [];
                    $code = $result['code'] ?? null;
                    $message = $result['message'] ?? null;
                    return PaymentResult::declined($this->providerName, $code, $message);
                }

                $body = $response->json();
                $result = $body['result'] ?? [];
                if (!empty($result['approved'])) {
                    return PaymentResult::approved($this->providerName, $result['ref'] ?? null, $result['ts'] ?? null);
                }

                return PaymentResult::declined($this->providerName, $result['code'] ?? null, $result['message'] ?? null);
            } catch (ConnectionException $e) {
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
