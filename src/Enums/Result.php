<?php

namespace Sarkhanrasimoghlu\PashaBank\Enums;

enum Result: string
{
    case OK = 'OK';
    case Failed = 'FAILED';
    case Created = 'CREATED';
    case Pending = 'PENDING';
    case Declined = 'DECLINED';
    case Reversed = 'REVERSED';
    case AutoReversed = 'AUTOREVERSED';
    case Timeout = 'TIMEOUT';
}
