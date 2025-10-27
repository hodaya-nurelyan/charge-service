<?php
namespace App\Services\Contracts;

use App\Services\PaymentResult;

interface PaymentGatewayInterface
{
    /**
     * Charge the payment and return a PaymentResult or throw GatewayException on gateway error
     * @param array $data
     * @return PaymentResult
     */
    public function charge(array $data): PaymentResult;
}
