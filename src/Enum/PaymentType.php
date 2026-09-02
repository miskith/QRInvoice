<?php

declare(strict_types=1);

namespace miskith\QRInvoice\Enum;

enum PaymentType: string
{
	case Instant = 'IP';
	case Standard = 'STD';
}
