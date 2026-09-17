<?php
declare(strict_types=1);

namespace App\Core\Payment;

use App\Core\Payment\Contracts\PaymentGatewayInterface;
use App\Gateways\Providers\Paypal\PaypalGateway;
use App\Gateways\Providers\Fride\FrideGateway;

class GatewayManager
{
    private static ?self $instance = null;
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    private function __construct()
    {
        $this->bootDefaultProviders();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function bootDefaultProviders(): void
    {
        // Register core providers
        $this->register(new PaypalGateway());
        $this->register(new FrideGateway());
    }

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[strtolower($gateway->getId())] = $gateway;
    }

    public function get(string $id): ?PaymentGatewayInterface
    {
        return $this->gateways[strtolower($id)] ?? null;
    }

    /**
     * @return array<string, PaymentGatewayInterface>
     */
    public function getAll(): array
    {
        return $this->gateways;
    }

    /**
     * Get active and properly configured gateways for checkout
     * @return array<string, PaymentGatewayInterface>
     */
    public function getActiveForCurrency(string $currency): array
    {
        $currency = strtoupper($currency);
        return array_filter($this->gateways, function (PaymentGatewayInterface $gateway) use ($currency) {
            if (!$gateway->isEnabled() || !$gateway->isConfigured()) {
                return false;
            }
            $supported = $gateway->getSupportedCurrencies();
            return empty($supported) || in_array('*', $supported, true) || in_array($currency, $supported, true);
        });
    }
}
