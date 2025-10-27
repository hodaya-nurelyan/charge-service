<?php
namespace App\Services;

use Exception;

class GatewayException extends Exception
{
    public bool $transient;
    public string $provider;

    public function __construct(string $message = "Gateway error", string $provider = 'unknown', bool $transient = false, int $code = 0)
    {
        parent::__construct($message, $code);
        $this->transient = $transient;
        $this->provider = $provider;
    }
}
