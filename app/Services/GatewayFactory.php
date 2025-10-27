<?php
namespace App\Services;

use App\Services\Gateways\AmexGateway;
use App\Services\Gateways\DinersGateway;
use App\Services\Gateways\MastercardGateway;
use App\Services\Contracts\PaymentGatewayInterface;

class GatewayFactory
{
    /**
     * @param string $type amex|diners|mastercard
     * @return PaymentGatewayInterface
     */
    public function make(string $type): PaymentGatewayInterface
    {
        return match (strtolower($type)) {
            'amex' => new AmexGateway(),
            'diners' => new DinersGateway(),
            'mastercard' => new MastercardGateway(),
            default => throw new \InvalidArgumentException("Unsupported gateway type: {$type}"),
        };
    }
}
