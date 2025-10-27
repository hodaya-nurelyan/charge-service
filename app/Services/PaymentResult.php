<?php
namespace App\Services;

class PaymentResult
{
    public bool $approved;
    public ?string $provider_reference;
    public ?string $code;
    public ?string $message;
    public ?string $timestamp;
    public string $provider;

    public function __construct(bool $approved, string $provider, ?string $provider_reference = null, ?string $code = null, ?string $message = null, ?string $timestamp = null)
    {
        $this->approved = $approved;
        $this->provider = $provider;
        $this->provider_reference = $provider_reference;
        $this->code = $code;
        $this->message = $message;
        $this->timestamp = $timestamp;
    }

    public static function approved(string $provider, string $provider_reference = null, string $timestamp = null): self
    {
        return new self(true, $provider, $provider_reference, null, null, $timestamp);
    }

    public static function declined(string $provider, ?string $code = null, ?string $message = null): self
    {
        return new self(false, $provider, null, $code, $message, null);
    }
}
