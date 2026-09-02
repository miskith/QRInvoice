<?php

declare(strict_types=1);

namespace miskith\QRInvoice\Enum;

enum Format: string
{
	case Png = 'png';
	case Svg = 'svg';
	case Webp = 'webp';
	case Gif = 'gif';
	case Pdf = 'pdf';
	case Eps = 'eps';
	case Binary = 'binary';
	case Bin = 'bin';
}
