<?php

/*
 * This file is part of the library "QRInvoice".
 *
 * (c) Dennis Fridrich <fridrich.dennis@gmail.com>
 *
 * For the full copyright and license information,
 * please view LICENSE.
 */

use miskith\QRInvoice\Enum\InvoiceDocumentType;
use miskith\QRInvoice\Enum\TaxPerformance;
use miskith\QRInvoice\QRInvoice;
use PHPUnit\Framework\TestCase;

/**
 * Class QRInvoiceTest.
 */
class QRInvoiceTest extends TestCase
{
	public function testSimpleInvoice(): void
	{
		$string = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setMessage('Düakrítičs')
			->setInvoiceId('123456789')
			->setInvoiceDate(new DateTime('2021-07-15'));

		$this->assertSame(
			'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:CZK*MSG:Duakritics*X-VS:2016001234*X-INV:SID%2A1.0%2AID:123456789%2ADD:20210715*',
			$string->__toString(),
		);
	}

	public function testSimpleOnlyInvoice(): void
	{
		$string = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setIsOnlyInvoice(true)
			->setMessage('Düakrítičs')
			->setInvoiceId('123456789')
			->setInvoiceDate(new DateTime('2021-07-15'));

		$this->assertSame(
			'SID*1.0*ID:123456789*DD:20210715*AM:1234.56*MSG:Duakritics*VS:2016001234*CC:CZK*ACC:CZ0301000000123456789012*',
			$string->__toString(),
		);
	}

	public function testFirstFromDocumentation(): void
	{
		$string = QRInvoice::create('27-16060243/0300', 495.00, '012150672')
			->setInvoiceId('012150672')
			->setDueDate(new DateTime('2015-12-17'))
			->setInvoiceDate(new DateTime('2015-12-01'))
			->setTaxDate(new DateTime('2015-12-01'))
			->setTaxPerformance(0)
			->setCompanyTaxId('CZ60194383')
			->setCompanyRegistrationId('60194383')
			->setInvoiceSubjectTaxId('CZ12345678')
			->setTaxBase(409.09, 0)
			->setTaxAmount(85.91, 0);

		$this->assertSame(
			'SPD*1.0*ACC:CZ3103000000270016060243*AM:495.00*CC:CZK*DT:20151217*X-VS:012150672*X-INV:SID%2A1.0%2AID:012150672%2ADD:20151201%2ATP:0%2AVII:CZ60194383%2AINI:60194383%2AVIR:CZ12345678%2ADUZP:20151201%2ATB0:409.09%2AT0:85.91*',
			$string->__toString(),
		);
	}

	public function testFirstFromDocumentationOnlyInvoice(): void
	{
		$string = QRInvoice::create('27-16060243/0300', 495.00, '012150672')
			->setIsOnlyInvoice(true)
			->setInvoiceId('012150672')
			->setDueDate(new DateTime('2015-12-17'))
			->setInvoiceDate(new DateTime('2015-12-01'))
			->setTaxDate(new DateTime('2015-12-01'))
			->setTaxPerformance(0)
			->setCompanyTaxId('CZ60194383')
			->setCompanyRegistrationId('60194383')
			->setInvoiceSubjectTaxId('CZ12345678')
			->setTaxBase(409.09, 0)
			->setTaxAmount(85.91, 0);

		$this->assertSame(
			'SID*1.0*ID:012150672*DD:20151201*AM:495.00*TP:0*VS:012150672*VII:CZ60194383*INI:60194383*VIR:CZ12345678*DUZP:20151201*DT:20151217*TB0:409.09*T0:85.91*CC:CZK*ACC:CZ3103000000270016060243*',
			$string->__toString(),
		);
	}

	public function testSecondFromDocumentation(): void
	{
		$string = new QRInvoice()
			->setIban('CZ9701000000007098760287+KOMBCZPP')
			->setAmount(61189.00)
			->setVariableSymbol('3310001054')
			->setInvoiceId('2001401154')
			->setInvoiceDocumentType(9)
			->setDueDate(new DateTime('2018-04-12'))
			->setInvoiceDate(new DateTime('2014-04-04'))
			->setTaxDate(new DateTime('2014-04-04'))
			->setTaxPerformance(0)
			->setCompanyTaxId('CZ25568736')
			->setCompanyRegistrationId('25568736')
			->setInvoiceSubjectTaxId('CZ25568736')
			->setInvoiceSubjectRegistrationId('25568736')
			->setMessage('Dodávka vybavení interiéru hotelu Kamzík')
			->setTaxBase(26492.70, 0)
			->setTaxAmount(5563.47, 0)
			->setTaxBase(25333.10, 1)
			->setTaxAmount(3799.97, 1)
			->setNoTaxAmount(-0.24)
			->setInvoiceIncludingDeposit(false)
			->setTaxSoftware('MoneyS5-1.7.1');

		$this->assertSame(
			'SPD*1.0*ACC:CZ9701000000007098760287+KOMBCZPP*AM:61189.00*CC:CZK*DT:20180412*MSG:Dodavka vybaveni interieru hotelu Kamzik*X-VS:3310001054*X-INV:SID%2A1.0%2AID:2001401154%2ADD:20140404%2ATP:0%2ATD:9%2ASA:0%2AVII:CZ25568736%2AINI:25568736%2AVIR:CZ25568736%2AINR:25568736%2ADUZP:20140404%2ATB0:26492.70%2AT0:5563.47%2ATB1:25333.10%2AT1:3799.97%2ANTB:-0.24%2AX-SW:MoneyS5-1.7.1*',
			$string->__toString(),
		);
	}

	public function testSecondFromDocumentationOnlyInvoice(): void
	{
		$string = new QRInvoice()
			->setIban('CZ9701000000007098760287+KOMBCZPP')
			->setAmount(61189.00)
			->setVariableSymbol('3310001054')
			->setIsOnlyInvoice(true)
			->setInvoiceId('2001401154')
			->setInvoiceDocumentType(9)
			->setDueDate(new DateTime('2014-04-12'))
			->setInvoiceDate(new DateTime('2014-04-04'))
			->setTaxDate(new DateTime('2014-04-04'))
			->setTaxPerformance(0)
			->setCompanyTaxId('CZ25568736')
			->setCompanyRegistrationId('25568736')
			->setInvoiceSubjectTaxId('CZ25568736')
			->setInvoiceSubjectRegistrationId('25568736')
			->setMessage('Dodávka vybavení interiéru hotelu Kamzík')
			->setTaxBase(26492.70, 0)
			->setTaxAmount(5563.47, 0)
			->setTaxBase(25333.10, 1)
			->setTaxAmount(3799.97, 1)
			->setNoTaxAmount(-0.24)
			->setInvoiceIncludingDeposit(false)
			->setTaxSoftware('MoneyS5-1.7.1');

		$this->assertSame(
			'SID*1.0*ID:2001401154*DD:20140404*AM:61189.00*TP:0*TD:9*SA:0*MSG:Dodavka vybaveni interieru hotelu Kamzik*VS:3310001054*VII:CZ25568736*INI:25568736*VIR:CZ25568736*INR:25568736*DUZP:20140404*DT:20140412*TB0:26492.70*T0:5563.47*TB1:25333.10*T1:3799.97*NTB:-0.24*CC:CZK*ACC:CZ9701000000007098760287+KOMBCZPP*X-SW:MoneyS5-1.7.1*',
			$string->__toString(),
		);
	}

	public function testSimpleInvoiceWithCrc32(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setMessage('Düakrítičs')
			->setInvoiceId('123456789')
			->setInvoiceDate(new DateTime('2021-07-15'))
			->setCRC32(true);

		$spdCrc = $qr->calculateSpdCrc32();
		$sidCrc = $qr->calculateSidCrc32();

		$this->assertSame($spdCrc, $qr->getCRC32());
		$this->assertNotEmpty($sidCrc);

		$expected = sprintf(
			'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:CZK*MSG:Duakritics*X-VS:2016001234*CRC32:%s*X-INV:SID%%2A1.0%%2AID:123456789%%2ADD:20210715%%2ACRC32:%s*',
			$spdCrc,
			$sidCrc,
		);

		$this->assertSame($expected, $qr->__toString());
	}

	public function testSimpleOnlyInvoiceWithCrc32(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setIsOnlyInvoice(true)
			->setMessage('Düakrítičs')
			->setInvoiceId('123456789')
			->setInvoiceDate(new DateTime('2021-07-15'))
			->setCRC32(true);

		$sidCrc = $qr->calculateSidCrc32();
		$this->assertSame($sidCrc, $qr->getCRC32());

		$expected = sprintf(
			'SID*1.0*ID:123456789*DD:20210715*AM:1234.56*MSG:Duakritics*VS:2016001234*CC:CZK*ACC:CZ0301000000123456789012*CRC32:%s*',
			$sidCrc,
		);

		$this->assertSame($expected, $qr->__toString());
	}

	public function testDocumentTypeAndTaxPerformanceEnums(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setIsOnlyInvoice(true)
			->setInvoiceId('123456789')
			->setInvoiceDate(new DateTime('2021-07-15'))
			->setInvoiceDocumentType(InvoiceDocumentType::CreditNote)
			->setTaxPerformance(TaxPerformance::SecondReduced);

		$this->assertStringContainsString('*TD:2*', $qr->__toString());
		$this->assertStringContainsString('*TP:2*', $qr->__toString());

		$qr->setInvoiceDocumentType(InvoiceDocumentType::Other)
			->setTaxPerformance(TaxPerformance::Standard);

		$this->assertStringContainsString('*TD:9*', $qr->__toString());
		$this->assertStringContainsString('*TP:0*', $qr->__toString());
	}
}
