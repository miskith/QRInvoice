<?php

declare(strict_types=1);

namespace miskith\QRInvoice\Enum;

enum InvoiceDocumentType: int
{
	case TaxInvoice = 0;
	case ProformaInvoice = 1;
	case CreditNote = 2;
	case DebitNote = 3;
	case Receipt = 4;
	case Correction = 5;
	case Other = 9;
}
