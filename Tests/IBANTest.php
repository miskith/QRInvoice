<?php

declare(strict_types=1);

/*
 * This file is part of the library "QRInvoice".
 *
 * (c) Dennis Fridrich <fridrich.dennis@gmail.com>
 *
 * For the full copyright and license information,
 * please view LICENSE.
 */

use miskith\QRInvoice\QRInvoice;
use PHPUnit\Framework\TestCase;

/**
 * Class QRInvoiceTest.
 */
final class IBANTest extends TestCase
{
	public function testAccountHigherThanMaxInt(): void
	{
		$string = QRInvoice::accountToIban('2501301193/2010');

		$this->assertSame(
			'CZ3620100000002501301193',
			$string,
		);
	}
}
