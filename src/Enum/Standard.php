<?php

declare(strict_types=1);

namespace miskith\QRInvoice\Enum;

enum Standard: string
{
	case Spayd = 'SPAYD';
	case Epc = 'EPC';
}
