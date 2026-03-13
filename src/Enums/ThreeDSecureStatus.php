<?php

namespace Sarkhanrasimoghlu\PashaBank\Enums;

enum ThreeDSecureStatus: string
{
    case Authenticated = 'AUTHENTICATED';
    case Declined = 'DECLINED';
    case NotParticipated = 'NOTPARTICIPATED';
    case NoRange = 'NO_RANGE';
    case Attempted = 'ATTEMPTED';
    case Unavailable = 'UNAVAILABLE';
}
