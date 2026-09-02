<?php

/*
 * This file is part of the library "QRInvoice".
 *
 * (c) Dennis Fridrich <fridrich.dennis@gmail.com>
 *
 * For the full copyright and license information,
 * please view LICENSE.
 */

use Endroid\QrCode\QrCode;
use miskith\QRInvoice\Enum\Currency;
use miskith\QRInvoice\Enum\Format;
use miskith\QRInvoice\Enum\PaymentType;
use miskith\QRInvoice\Enum\Standard;
use miskith\QRInvoice\QRInvoice;
use miskith\QRInvoice\QRInvoiceException;
use PHPUnit\Framework\TestCase;

/**
 * Class QRPlatbaTest.
 */
class QRPlatbaTest extends TestCase
{
	public function testFakeCurrencyString(): void
	{
		$this->expectException(InvalidArgumentException::class);

		QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setMessage('Düakrítičs')
			->setCurrency('FAKE');
	}

	public function testCzkString(): void
	{
		$string = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setMessage('Düakrítičs');

		$this->assertSame(
			'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:CZK*MSG:Duakritics*X-VS:2016001234',
			$string->__toString(),
		);

		$string = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setMessage('Düakrítičs')
			->setCurrency('CZK');

		$this->assertSame(
			'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:CZK*MSG:Duakritics*X-VS:2016001234',
			$string->__toString(),
		);
	}

	public function testEurString(): void
	{
		$string = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setMessage('Düakrítičs')
			->setCurrency('EUR');

		$this->assertSame(
			'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:EUR*MSG:Duakritics*X-VS:2016001234',
			$string->__toString(),
		);
	}

	public function testCreateWithCurrency(): void
	{
		$string = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234', 'EUR')
			->setMessage('Düakrítičs');

		$this->assertSame(
			'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:EUR*MSG:Duakritics*X-VS:2016001234',
			$string->__toString(),
		);
	}

	public function testQrCodeInstante(): void
	{
		$qrInvoice = QRInvoice::create('12-3456789012/0100', 987.60)
			->setMessage('QR platba je parádní!')
			->getQRCodeInstance();

		$this->assertInstanceOf(QrCode::class, $qrInvoice);
	}

	public function testQrCodeBase64Instante(): void
	{
		$qrInvoice = QRInvoice::create('12-3456789012/0100', 987.60)
			->setMessage('QR platba musí fungovat i jako HTML!')
			->getQRCodeImage(false);

		$this->assertStringStartsWith('data:image/png;base64,', $qrInvoice);
	}

	public function testQrCodeHTMLImageInstante(): void
	{
		$qrInvoice = QRInvoice::create('12-3456789012/0100', 987.60)
			->setMessage('QR platba musí fungovat i jako HTML!')
			->getQRCodeImage();

		$this->assertNotEmpty($qrInvoice);
	}

	public function testQrCodePngFileIsCreated(): void
	{
		$temp_name = tempnam(sys_get_temp_dir(), 'QrCode');

		$this->assertTrue(is_file($temp_name), 'Could not create temp file.');
		$this->assertEmpty(file_get_contents($temp_name), 'Temp file is not empty.');

		new QRInvoice()->setAccount('12-3456789012/0100')
			->setVariableSymbol('2016001234')
			->setMessage('Toto je testovací QR platba.')
			->setSpecificSymbol('0308')
			->setSpecificSymbol('1234')
			->setCurrency('CZK')
			->setDueDate(new DateTime())
			->saveQRCodeImage($temp_name, 'png', 100, 5);

		$this->assertNotEmpty(file_get_contents($temp_name), 'QR code image for payment could not be created into the temp dir.');
		unlink($temp_name);
	}

	public function testQrCodeSvgFileIsCreated(): void
	{
		$temp_name = tempnam(sys_get_temp_dir(), 'QrCode');

		$this->assertTrue(is_file($temp_name), 'Could not create temp file.');
		$this->assertEmpty(file_get_contents($temp_name), 'Temp file is not empty.');

		new QRInvoice()->setAccount('12-3456789012/0100')
			->setVariableSymbol('2016001234')
			->setMessage('Toto je testovací QR platba.')
			->setSpecificSymbol('0308')
			->setSpecificSymbol('1234')
			->setCurrency('CZK')
			->setDueDate(new DateTime())
			->saveQRCodeImage($temp_name, 'svg', 300, 20);

		$this->assertNotEmpty(file_get_contents($temp_name), 'QR code image for payment could not be created into the temp dir.');
		unlink($temp_name);
	}

	public function testQrCodeWebpFileIsCreated(): void
	{
		$temp_name = tempnam(sys_get_temp_dir(), 'QrCode');

		$this->assertTrue(is_file($temp_name), 'Could not create temp file.');
		$this->assertEmpty(file_get_contents($temp_name), 'Temp file is not empty.');

		new QRInvoice()->setAccount('12-3456789012/0100')
			->setVariableSymbol('2016001234')
			->setMessage('Toto je testovací QR platba.')
			->setCurrency('CZK')
			->setDueDate(new DateTime())
			->saveQRCodeImage($temp_name, 'webp', 100, 5);

		$this->assertNotEmpty(file_get_contents($temp_name), 'QR code image for payment could not be created into the temp dir.');
		unlink($temp_name);
	}

	public function testQrCodeGifFileIsCreated(): void
	{
		$temp_name = tempnam(sys_get_temp_dir(), 'QrCode');

		$this->assertTrue(is_file($temp_name), 'Could not create temp file.');
		$this->assertEmpty(file_get_contents($temp_name), 'Temp file is not empty.');

		new QRInvoice()->setAccount('12-3456789012/0100')
			->setVariableSymbol('2016001234')
			->setMessage('Toto je testovací QR platba.')
			->setCurrency('CZK')
			->setDueDate(new DateTime())
			->saveQRCodeImage($temp_name, 'gif', 100, 5);

		$this->assertNotEmpty(file_get_contents($temp_name), 'QR code image for payment could not be created into the temp dir.');
		unlink($temp_name);
	}

	public function testRecipientName(): void
	{
		$string = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setRecipientName('Düakrítičs');

		$this->assertSame(
			'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:CZK*X-VS:2016001234*RN:Duakritics',
			$string->__toString(),
		);
	}

	public function testCrc32AutomaticCalculation(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setMessage('Düakrítičs')
			->setCurrency('CZK')
			->setCRC32(true);

		$expectedCrc = '15EF68DE';
		$this->assertSame($expectedCrc, $qr->getCRC32());
		$this->assertSame($expectedCrc, $qr->calculateSpdCrc32());
		$this->assertSame(
			'SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.56*CC:CZK*MSG:Duakritics*X-VS:2016001234*CRC32:' . $expectedCrc,
			$qr->__toString(),
		);
	}

	public function testCrc32OfficialSpecificationExample(): void
	{
		$qr = new QRInvoice()
			->setIban('CZ5855000000001265098001')
			->setAmount(100.00)
			->setCurrency('CZK')
			->setCRC32(true);

		$this->assertSame('AAD80227', $qr->getCRC32());
		$this->assertSame(
			'SPD*1.0*ACC:CZ5855000000001265098001*AM:100.00*CC:CZK*CRC32:AAD80227',
			$qr->__toString(),
		);
	}

	public function testCrc32DefaultParam(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setCRC32();

		$this->assertNotNull($qr->getCRC32());
		$this->assertStringContainsString('CRC32:' . $qr->getCRC32(), $qr->__toString());
	}

	public function testCrc32Disable(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setCRC32(true)
			->setCRC32(false);

		$this->assertNull($qr->getCRC32());
		$this->assertStringNotContainsString('CRC32', $qr->__toString());
	}

	public function testCrc32AliasMethods(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setCrc32(true);

		$this->assertNotNull($qr->getCrc32());
		$this->assertSame($qr->getCRC32(), $qr->getCrc32());
	}

	public function testInstantPayment(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setInstantPayment(true);

		$this->assertStringContainsString('*PT:IP', $qr->__toString());

		$qr->setInstantPayment(false);
		$this->assertStringNotContainsString('PT:IP', $qr->__toString());
	}

	public function testPaymentTypeCustom(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setPaymentType('STD');

		$this->assertStringContainsString('*PT:STD', $qr->__toString());
	}

	public function testPaymentTypeTooLongThrowsException(): void
	{
		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('Payment type (PT) cannot exceed 3 characters.');

		new QRInvoice()->setPaymentType('TOOLONG');
	}

	public function testAlternativeAccounts(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setAlternativeAccounts([
				'CZ5855000000001265098001+RZBCCZPP',
				'2501301193/2010',
			]);

		$this->assertStringContainsString('*ALT-ACC:CZ5855000000001265098001+RZBCCZPP,CZ3620100000002501301193', $qr->__toString());
	}

	public function testAddAlternativeAccount(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->addAlternativeAccount('CZ5855000000001265098001+RZBCCZPP')
			->addAlternativeAccount('2501301193/2010');

		$this->assertStringContainsString('*ALT-ACC:CZ5855000000001265098001+RZBCCZPP,CZ3620100000002501301193', $qr->__toString());
	}

	public function testAlternativeAccountsTooManyThrowsException(): void
	{
		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('Maximum of 2 alternative accounts is allowed.');

		new QRInvoice()->setAlternativeAccounts([
			'CZ5855000000001265098001',
			'CZ0920100000002501301193',
			'CZ0301000000123456789012',
		]);
	}

	public function testNotificationEmail(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setNotificationEmail('faktury@firma.cz');

		$this->assertStringContainsString('*NT:E*NTA:faktury@firma.cz', $qr->__toString());

		$qr->clearNotification();
		$this->assertStringNotContainsString('NT:E', $qr->__toString());
		$this->assertStringNotContainsString('NTA:', $qr->__toString());
	}

	public function testNotificationEmailInvalidThrowsException(): void
	{
		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('Invalid notification email "invalid-email".');

		new QRInvoice()->setNotificationEmail('invalid-email');
	}

	public function testNotificationPhone(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setNotificationPhone('+420 123 456 789');

		$this->assertStringContainsString('*NT:P*NTA:+420123456789', $qr->__toString());
	}

	public function testNotificationPhoneInvalidThrowsException(): void
	{
		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('Invalid notification phone "abc".');

		new QRInvoice()->setNotificationPhone('abc');
	}

	public function testInternalId(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setInternalId('ORDER-12345');

		$this->assertStringContainsString('*X-ID:ORDER-12345', $qr->__toString());
	}

	public function testInternalIdTooLongThrowsException(): void
	{
		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('Internal ID (X-ID) cannot exceed 20 characters.');

		new QRInvoice()->setInternalId('123456789012345678901');
	}

	public function testUrl(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setUrl('https://example.com/pay');

		$this->assertStringContainsString('*X-URL:https://example.com/pay', $qr->__toString());
	}

	public function testUrlTooLongThrowsException(): void
	{
		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('URL (X-URL) cannot exceed 140 characters.');

		new QRInvoice()->setUrl(str_repeat('a', 141));
	}

	public function testRepeat(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '1234.56', '2016001234')
			->setRepeat(7);

		$this->assertStringContainsString('*X-PER:7', $qr->__toString());
	}

	public function testRepeatInvalidThrowsException(): void
	{
		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('Repeat period (X-PER) must be between 1 and 30 days.');

		new QRInvoice()->setRepeat(31);
	}

	public function testCurrencyEnum(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '100.00')
			->setCurrency(Currency::EUR);

		$this->assertStringContainsString('*CC:EUR', $qr->__toString());

		$qr->setCurrency(Currency::CZK);
		$this->assertStringContainsString('*CC:CZK', $qr->__toString());
	}

	public function testPaymentTypeEnum(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '100.00')
			->setPaymentType(PaymentType::Instant);

		$this->assertStringContainsString('*PT:IP', $qr->__toString());

		$qr->setPaymentType(PaymentType::Standard);
		$this->assertStringContainsString('*PT:STD', $qr->__toString());
	}

	public function testSaveQRCodeImageWithFormatEnum(): void
	{
		$qr = QRInvoice::create('12-3456789012/0100', '100.00');
		$tmpPng = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
		$tmpSvg = tempnam(sys_get_temp_dir(), 'qr_') . '.svg';

		try {
			$qr->saveQRCodeImage($tmpPng, Format::Png, 100, 5);
			$this->assertFileExists($tmpPng);
			$this->assertGreaterThan(0, filesize($tmpPng));

			$qr->saveQRCodeImage($tmpSvg, Format::Svg, 100, 5);
			$this->assertFileExists($tmpSvg);
			$this->assertStringContainsString('<svg', file_get_contents($tmpSvg));
		} finally {
			if (file_exists($tmpPng)) {
				unlink($tmpPng);
			}
			if (file_exists($tmpSvg)) {
				unlink($tmpSvg);
			}
		}
	}

	public function testDisabledValidationByDefaultAllowsDummyAccount(): void
	{
		$qr = new QRInvoice();
		$this->assertFalse($qr->isValidateAccount());

		$qr->setAccount('12-3456789012/0100');
		$this->assertStringContainsString('ACC:CZ0301000000123456789012', $qr->__toString());
	}

	public function testEnabledValidationThrowsOnInvalidAccount(): void
	{
		$qr = new QRInvoice();
		$qr->setValidateAccount(true);

		$this->assertTrue($qr->isValidateAccount());
		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('is not a valid Czech bank account (modulo 11 check failed)');

		$qr->setAccount('12-3456789012/0100');
	}

	public function testValidCzechAccountPassesValidationWhenEnabled(): void
	{
		$qr1 = new QRInvoice();
		$qr1->setValidateAccount(true)->setAccount('222885/5500');
		$this->assertNotEmpty($qr1->__toString());

		$qr2 = new QRInvoice();
		$qr2->setValidateAccount(true)->setAccount('27-16060243/0300');
		$this->assertNotEmpty($qr2->__toString());

		$qr3 = new QRInvoice();
		$qr3->setValidateAccount(true)->setAccount('19-2000145399/0800');
		$this->assertNotEmpty($qr3->__toString());
	}

	public function testStaticValidateCzechAccount(): void
	{
		$this->assertTrue(QRInvoice::validateCzechAccount('222885/5500'));
		$this->assertTrue(QRInvoice::validateCzechAccount('27-16060243/0300'));
		$this->assertTrue(QRInvoice::validateCzechAccount('19-2000145399/0800'));
		$this->assertTrue(QRInvoice::validateCzechAccount('2501301193/2010'));

		$this->assertFalse(QRInvoice::validateCzechAccount('12-3456789012/0100'));
		$this->assertFalse(QRInvoice::validateCzechAccount('1234567890/0100'));
		$this->assertFalse(QRInvoice::validateCzechAccount('invalid-account'));
		$this->assertFalse(QRInvoice::validateCzechAccount('123/010'));
	}

	public function testAlternativeAccountValidationWhenEnabled(): void
	{
		$qr = new QRInvoice();
		$qr->setValidateAccount(true)->setAccount('222885/5500');

		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('Alternative account number "12-3456789012/0100" is not a valid Czech bank account');

		$qr->addAlternativeAccount('12-3456789012/0100');
	}

	public function testSlovakAccountToIban(): void
	{
		$iban = QRInvoice::accountToIban('1234567890/0200', 'SK');
		$this->assertSame('SK6702000000001234567890', $iban);

		// ISO 7064 Mod 97 ověření
		$checkStr = substr($iban, 4) . '2820' . substr($iban, 2, 2);
		$this->assertSame('1', bcmod($checkStr, '97'));
	}

	public function testSetSlovakAccount(): void
	{
		$qr = new QRInvoice();
		$qr->setSlovakAccount('1234567890/0200');

		$this->assertStringContainsString('ACC:SK6702000000001234567890', $qr->__toString());
	}

	public function testSlovakAccountValidationWhenEnabled(): void
	{
		$this->assertTrue(QRInvoice::validateSlovakAccount('222885/0200'));
		$this->assertFalse(QRInvoice::validateSlovakAccount('12-3456789012/0900'));

		$qr = new QRInvoice();
		$qr->setValidateAccount(true);

		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('is not a valid Slovak bank account (modulo 11 check failed)');

		$qr->setSlovakAccount('12-3456789012/0900');
	}

	public function testEpcStandardGeneration(): void
	{
		$qr = new QRInvoice();
		$qr->setStandard(Standard::Epc)
			->setRecipientName('Firma s.r.o.')
			->setIban('SK6702000000001234567890')
			->setBic('SUBAASKBX')
			->setAmount(150.00)
			->setCurrency(Currency::EUR)
			->setVariableSymbol('2026001')
			->setMessage('Platba objednavky');

		$this->assertSame(Standard::Epc, $qr->getStandard());
		$this->assertSame('SUBAASKBX', $qr->getBic());

		$expected = implode("\n", [
			'BCD',
			'002',
			'1',
			'SCT',
			'SUBAASKBX',
			'Firma s.r.o.',
			'SK6702000000001234567890',
			'EUR150.00',
			'',
			'',
			'VS:2026001 Platba objednavky',
		]);

		$this->assertSame($expected, (string) $qr);
		$this->assertSame($expected, $qr->getEpcString());
	}

	public function testEpcStructuredReference(): void
	{
		$qr = new QRInvoice();
		$qr->setStandard(Standard::Epc)
			->setRecipientName('ACME Europe')
			->setIban('SK6702000000001234567890')
			->setAmount(99.00)
			->setCurrency(Currency::EUR)
			->setRemittanceReference('RF18539007547034')
			->setPurpose('GDDS');

		$this->assertSame('RF18539007547034', $qr->getRemittanceReference());
		$this->assertSame('GDDS', $qr->getPurpose());

		$expected = implode("\n", [
			'BCD',
			'002',
			'1',
			'SCT',
			'',
			'ACME Europe',
			'SK6702000000001234567890',
			'EUR99.00',
			'GDDS',
			'RF18539007547034',
		]);

		$this->assertSame($expected, (string) $qr);
	}

	public function testEpcRequiresRecipientName(): void
	{
		$qr = new QRInvoice();
		$qr->setStandard(Standard::Epc)
			->setIban('SK6702000000001234567890');

		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('Recipient name (setRecipientName) is required for SEPA EPC QR code.');

		$qr->getEpcString();
	}

	public function testEpcRequiresEurCurrency(): void
	{
		$qr = new QRInvoice();
		$qr->setStandard(Standard::Epc)
			->setRecipientName('ACME Europe')
			->setIban('SK6702000000001234567890')
			->setCurrency(Currency::CZK);

		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('SEPA EPC QR code requires EUR currency.');

		$qr->getEpcString();
	}

	public function testEpcQRCodeImageGeneration(): void
	{
		$qr = new QRInvoice();
		$qr->setStandard(Standard::Epc)
			->setRecipientName('Jan Novak')
			->setIban('SK6702000000001234567890')
			->setAmount(25.50);

		$img = $qr->getQRCodeImage();
		$this->assertStringStartsWith('<img src="data:image/png;base64,', $img);
	}

	public function testWithDefaultLogo(): void
	{
		$qr = new QRInvoice();
		$qr->setAccount('27-16060243/0300')
			->setAmount(100.00)
			->withDefaultLogo(45);

		$this->assertNotNull($qr->getLogo());
		$this->assertStringContainsString('qr-platba-logo.png', $qr->getLogo()->getPath());
		$this->assertSame(45, $qr->getLogo()->getResizeToWidth());
		$this->assertSame(45, $qr->getLogo()->getResizeToHeight());

		$img = $qr->getQRCodeImage();
		$this->assertStringStartsWith('<img src="data:image/png;base64,', $img);
	}

	public function testSetCustomLogo(): void
	{
		$qr = new QRInvoice();
		$qr->setAccount('27-16060243/0300')
			->setAmount(100.00)
			->setLogo(__DIR__ . '/../readme/sample-logo.png', 60, 60);

		$this->assertNotNull($qr->getLogo());
		$this->assertSame(60, $qr->getLogo()->getResizeToWidth());

		$qr->setLogo(null);
		$this->assertNull($qr->getLogo());
	}

	public function testSetLogoThrowsOnNonExistentFile(): void
	{
		$qr = new QRInvoice();

		$this->expectException(QRInvoiceException::class);
		$this->expectExceptionMessage('does not exist');

		$qr->setLogo('non_existent_file_path.png');
	}
}
