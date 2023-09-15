<?php

declare(strict_types=1);

namespace App\Enums;

enum RecordType: string
{
    case A = 'A';
    case AAAA = 'AAAA';
    case CNAME = 'CNAME';
    case MX = 'MX';
    case NS = 'NS';
    case SRV = 'SRV';
    case TXT = 'TXT';
    case SOA = 'SOA';
}
