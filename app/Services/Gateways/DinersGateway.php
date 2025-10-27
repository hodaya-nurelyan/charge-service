<?php
namespace App\Services\Gateways;

use App\Services\Contracts\PaymentGatewayInterface;
use App\Services\PaymentResult;
use App\Services\GatewayException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class DinersGateway implements PaymentGatewayInterface
{
    protected string $base;
    protected string $key;
    protected string $providerName = 'GatewayB';

    public function __construct()
    {
        $this->base = config('payments.b.base');
        $this->key = config('payments.b.key');
    }

    public function charge(array $data): PaymentResult
    {
        $url = rtrim($this->base, '/') . '/charge';

        $payload = [
            'amount' => number_format($data['price'], 2, '.', ''),
            'curr' => $data['currency'],
            'reference' => $data['ride_id'],
            'brand' => 'DINERS',
            'card_token' => $data['card_token'],
        ];

        $attempts = 2;
        $sleep = 100000;

        for ($attempt = 0; $attempt <= $attempts; $attempt++) {
            try {
                $response = Http::withHeaders(['X-API-KEY' => $this->key])
                    ->timeout(2.5)
                    ->asForm()
                    ->post($url, $payload);

                if ($response->serverError()) {
                    throw new \Exception('server_error');
                }

                if ($response->clientError()) {
                    $body = $response->json();
                    $code = $body['error_code'] ?? null;
                    $message = $body['message'] ?? null;
                    return PaymentResult::declined($this->providerName, $code, $message);
                }

                $body = $response->json();
                return PaymentResult::approved($this->providerName, $body['ref'] ?? null, $body['timestamp'] ?? null);
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
