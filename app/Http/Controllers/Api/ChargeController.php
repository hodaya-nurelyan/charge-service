<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChargeRequest;
use App\Services\GatewayFactory;
use App\Services\GatewayException;
use Illuminate\Http\JsonResponse;

class ChargeController extends Controller
{
    protected GatewayFactory $factory;

    public function __construct(GatewayFactory $factory)
    {
        $this->factory = $factory;
    }

    public function __invoke(ChargeRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $gateway = $this->factory->make($data['payment_method_type']);
            $result = $gateway->charge($data);

            if ($result->approved) {
                return response()->json([
                    'status' => 'approved',
                    'ride_id' => $data['ride_id'],
                    'amount' => $data['price'],
                    'currency' => $data['currency'],
                    'provider' => $result->provider,
                    'provider_reference' => $result->provider_reference,
                    'card_token' => $data['card_token'],
                    'authorized_at' => $result->timestamp,
                ], 200);
            }

            // Declined
            $unifiedCode = $this->mapProviderCode($result->code);

            return response()->json([
                'status' => 'declined',
                'ride_id' => $data['ride_id'],
                'code' => $unifiedCode,
                'message' => $result->message,
                'provider' => $result->provider,
                'card_token' => $data['card_token'],
            ], 402);
        } catch (GatewayException $e) {
            $payload = ['status' => 'error', 'message' => $e->getMessage(), 'provider' => $e->provider];
            $status = $e->transient ? 504 : 502;
            return response()->json($payload, $status);
        }
    }

    protected function mapProviderCode(?string $code): string
    {
        return match ($code) {
            'insufficient_funds' => 'insufficient_funds',
            'card_declined' => 'card_declined',
            'card_expired' => 'card_expired',
            'invalid_card' => 'invalid_card',
            default => 'provider_error',
        };
    }
}
