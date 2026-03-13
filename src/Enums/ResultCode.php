<?php

namespace Sarkhanrasimoghlu\PashaBank\Enums;

enum ResultCode: string
{
    case Approved = '000';
    case Decline = '100';
    case DeclineExpiredCard = '101';
    case DeclineSuspectedFraud = '102';
    case DeclineContactAcquirer = '103';
    case DeclineReferToIssuer = '107';
    case DeclineRouteNotFound = '108';
    case DeclineInvalidAmount = '110';
    case DeclineInvalidCardNumber = '111';
    case DeclineFunctionNotSupported = '115';
    case DeclineInsufficientFunds = '116';
    case DeclineNoCardRecord = '118';
    case DeclineNotPermittedToCardholder = '119';
    case DeclineNotPermittedToTerminal = '120';
    case DeclineSecurityViolation = '122';
    case DeclineCardNotEffective = '125';
    case DeclineCounterfeitCard = '129';
    case ReversalAccepted = '400';
    case ReconciledInBalance = '500';
    case ReconciledOutOfBalance = '501';
    case DeclineIssuerInoperative = '907';
    case DeclineRoutingNotFound = '908';
    case DeclineSystemMalfunction = '909';
    case DeclineIssuerTimedOut = '911';
    case DeclineIssuerUnavailable = '912';
    case DeclineReversalNotFound = '914';

    public function description(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::Decline => 'Decline (general)',
            self::DeclineExpiredCard => 'Decline, expired card',
            self::DeclineSuspectedFraud => 'Decline, suspected fraud',
            self::DeclineContactAcquirer => 'Decline, contact acquirer',
            self::DeclineReferToIssuer => 'Decline, refer to card issuer',
            self::DeclineRouteNotFound => 'Decline, route not found',
            self::DeclineInvalidAmount => 'Decline, invalid amount',
            self::DeclineInvalidCardNumber => 'Decline, invalid card number',
            self::DeclineFunctionNotSupported => 'Decline, function not supported',
            self::DeclineInsufficientFunds => 'Decline, insufficient funds',
            self::DeclineNoCardRecord => 'Decline, no card record',
            self::DeclineNotPermittedToCardholder => 'Decline, not permitted to cardholder',
            self::DeclineNotPermittedToTerminal => 'Decline, not permitted to terminal',
            self::DeclineSecurityViolation => 'Decline, security violation',
            self::DeclineCardNotEffective => 'Decline, card not effective',
            self::DeclineCounterfeitCard => 'Decline, suspected counterfeit card',
            self::ReversalAccepted => 'Reversal accepted',
            self::ReconciledInBalance => 'Reconciled, in balance',
            self::ReconciledOutOfBalance => 'Reconciled, out of balance',
            self::DeclineIssuerInoperative => 'Decline, card issuer inoperative',
            self::DeclineRoutingNotFound => 'Decline, routing not found',
            self::DeclineSystemMalfunction => 'Decline, system malfunction',
            self::DeclineIssuerTimedOut => 'Decline, card issuer timed out',
            self::DeclineIssuerUnavailable => 'Decline, card issuer unavailable',
            self::DeclineReversalNotFound => 'Decline, reversal original not found',
        };
    }
}
