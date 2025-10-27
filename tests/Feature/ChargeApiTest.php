<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChargeApiTest extends TestCase
{
    public function test_mastercard_success()
    {
        Http::fake([
            config('payments.c.base') . '/payments/create' => Http::response(['result' => ['approved' => true, 'ref' => 'C-778899', 'ts' => '2025-01-01T12:34:56Z']], 200),
        ]);

        $res = $this->postJson('/api/charge', [
            'ride_id' => 'RIDE-1', 'price' => 11.5, 'currency' => 'ILS',
            'payment_method_type' => 'mastercard', 'card_token' => 'tok_123'
        ]);

        $res->assertOk()->assertJsonPath('provider_reference', 'C-778899');
    }

    public function test_amex_success()
    {
        Http::fake([
            config('payments.a.base') . '/v1/charges' => Http::response(['id' => 'txn_9f82ab', 'status' => 'approved', 'processed_at' => '2025-01-01T12:34:56Z'], 200),
        ]);

        $res = $this->postJson('/api/charge', [
            'ride_id' => 'RIDE-A', 'price' => 11.5, 'currency' => 'ILS',
            'payment_method_type' => 'amex', 'card_token' => 'tok_a'
        ]);

        $res->assertOk()->assertJsonPath('provider_reference', 'txn_9f82ab');
    }

    public function test_diners_success()
    {
        Http::fake([
            config('payments.b.base') . '/charge' => Http::response(['status' => 'ok', 'ref' => 'B-556677', 'timestamp' => '2025-01-01T12:34:56Z'], 200),
        ]);

        $res = $this->postJson('/api/charge', [
            'ride_id' => 'RIDE-B', 'price' => 11.5, 'currency' => 'ILS',
            'payment_method_type' => 'diners', 'card_token' => 'tok_b'
        ]);

        $res->assertOk()->assertJsonPath('provider_reference', 'B-556677');
    }

    public function test_decline_returns_402()
    {
        Http::fake([
            config('payments.b.base') . '/charge' => Http::response(['status' => 'failed', 'error_code' => 'card_declined', 'message' => 'Not enough funds'], 400),
        ]);

        $res = $this->postJson('/api/charge', [
            'ride_id' => 'RIDE-B', 'price' => 11.5, 'currency' => 'ILS',
            'payment_method_type' => 'diners', 'card_token' => 'tok_b'
        ]);

        $res->assertStatus(402)->assertJsonPath('code', 'card_declined');
    }

    public function test_timeout_then_success_retries_and_succeeds()
    {
        Http::fake([
            config('payments.c.base') . '/payments/create' => Http::sequence()
                ->push('', 504)
                ->push(['result' => ['approved' => true, 'ref' => 'C-778899', 'ts' => '2025-01-01T12:34:56Z']], 200),
        ]);

        $res = $this->postJson('/api/charge', [
            'ride_id' => 'RIDE-RETRY', 'price' => 11.5, 'currency' => 'ILS',
            'payment_method_type' => 'mastercard', 'card_token' => 'tok_retry'
        ]);

        $res->assertOk()->assertJsonPath('provider_reference', 'C-778899');
    }

    public function test_repeated_5xx_returns_502()
    {
        Http::fake([
            config('payments.a.base') . '/v1/charges' => Http::sequence()->push('', 500)->push('', 500)->push('', 500),
        ]);

        $res = $this->postJson('/api/charge', [
            'ride_id' => 'RIDE-FAIL', 'price' => 11.5, 'currency' => 'ILS',
            'payment_method_type' => 'amex', 'card_token' => 'tok_fail'
        ]);

        $res->assertStatus(504)->assertJsonPath('status', 'error');
    }
}
