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
    case RecurringRegisterAuth = 'z';
    case RecurringRegisterTransaction = 'd';
    case RecurringRegisterOnly = 'p';
    case RecurringExecute = 'e';
    case RecurringDelete = 'x';
}
