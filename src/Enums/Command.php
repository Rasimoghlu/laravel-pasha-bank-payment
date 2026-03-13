<?php

namespace Sarkhanrasimoghlu\PashaBank\Enums;

enum Command: string
{
    case SmsTransaction = 'v';
    case DmsAuthorization = 'a';
    case DmsCapture = 't';
    case Reversal = 'r';
    case Refund = 'k';
    case TransactionResult = 'c';
    case EndOfBusinessDay = 'b';
}
