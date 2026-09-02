<?php

declare(strict_types=1);

namespace miskith\QRInvoice\Enum;

enum TaxPerformance: int
{
	case Standard = 0;
	case FirstReduced = 1;
	case SecondReduced = 2;
}
