<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Enums;

/**
 * How a plan is priced.
 */
enum PricingType: string
{
    case Free = 'free';
    case Subscription = 'subscription';
    case OneTime = 'one_time';
    case Installment = 'installment';
    case OneTimeInstallment = 'one_time_installment';
    case TokenGated = 'token_gated';
    case NonPaid = 'nonpaid';

    /**
     * A human-friendly label for display.
     */
    public function toFriendly(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Subscription => 'Subscription',
            self::OneTime => 'One-time',
            self::Installment => 'Installment',
            self::OneTimeInstallment => 'One-time installment',
            self::TokenGated => 'Token gated',
            self::NonPaid => 'Non-paid',
        };
    }
}
