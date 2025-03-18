<?php

/*
 * This file is part of the library "QRInvoice".
 *
 * (c) Dennis Fridrich <fridrich.dennis@gmail.com>
 *
 * For the full copyright and license information,
 * please view LICENSE.
 */

namespace miskith\QRInvoice;

use Endroid\QrCode\ErrorCorrectionLevel as QrErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode as QrRoundBlockSizeMode;
use Endroid\QrCode\Writer\BinaryWriter as QrBinaryWriter;
use Endroid\QrCode\Encoding\Encoding as QrEncoding;
use Endroid\QrCode\Writer\PngWriter as QrPngWriter;
use Endroid\QrCode\Writer\EpsWriter as QrEpsWriter;
use Endroid\QrCode\Writer\PdfWriter as QrPdfWriter;
use Endroid\QrCode\Writer\SvgWriter as QrSvgWriter;
use Endroid\QrCode\Color\Color as QrColor;
use Endroid\QrCode\QrCode;
use DateTime;

/**
 * Knihovna pro generování QR plateb v PHP.
 *
 * @see https://raw.githubusercontent.com/snoblucha/QRPlatba/master/QRPlatba.php
 */
class QRInvoice
{
	/**
	 * Verze QR formátu QR Platby.
	 */
	public const SPD_VERSION = '1.0';

	/**
	 * Verze QR formátu QR Faktury.
	 */
	public const SID_VERSION = '1.0';

	/**
	 * @var array
	 */
	private static $currencies = [
		'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
		'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL',
		'BSD', 'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY',
		'COP', 'CRC', 'CUC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD',
		'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GGP', 'GHS',
		'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF',
		'IDR', 'ILS', 'IMP', 'INR', 'IQD', 'IRR', 'ISK', 'JEP', 'JMD', 'JOD',
		'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KPW', 'KRW', 'KWD', 'KYD', 'KZT',
		'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD',
		'MMK', 'MNT', 'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN',
		'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK',
		'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR',
		'SBD', 'SCR', 'SDG', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SPL', 'SRD',
		'STD', 'SVC', 'SYP', 'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY',
		'TTD', 'TVD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VEF',
		'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XDR', 'XOF', 'XPF', 'YER', 'ZAR',
		'ZMW', 'ZWD',
	];

	/**
	 * @var array klíče QR Platby
	 */
	private array $spd_keys = [
		'ACC' => null,
		// Max. 46 - znaků IBAN, BIC Identifikace protistrany !povinny
		'ALT-ACC' => null,
		// Max. 93 - znaků Seznam alternativnich uctu. odddeleny carkou,
		'AM' => null,
		// Max. 10 znaků - Desetinné číslo Výše částky platby.
		'CC' => 'CZK',
		// Právě 3 znaky - Měna platby.
		'DT' => null,
		// Právě 8 znaků - Datum splatnosti YYYYMMDD.
		'MSG' => null,
		// Max. 60 znaků - Zpráva pro příjemce.
		'X-VS' => null,
		// Max. 10 znaků - Celé číslo - Variabilní symbol
		'X-SS' => null,
		// Max. 10 znaků - Celé číslo - Specifický symbol
		'X-KS' => null,
		// Max. 10 znaků - Celé číslo - Konstantní symbol
		'RF' => null,
		// Max. 16 znaků - Identifikátor platby pro příjemce.
		'RN' => null,
		// Max. 35 znaků - Jméno příjemce.
		'PT' => null,
		// Právě 3 znaky - Typ platby.
		'CRC32' => null,
		// Právě 8 znaků - Kontrolní součet - HEX.
		'NT' => null,
		// Právě 1 znak P|E - Identifikace kanálu pro zaslání notifikace výstavci platby.
		'NTA' => null,
		// Max. 320 znaků - Telefonní číslo v mezinárodním nebo lokálním vyjádření nebo E-mailová adresa
		'X-PER' => null,
		// Max. 2 znaky - Celé číslo - Počet dní, po které se má provádět pokus o opětovné provedení neúspěšné platby
		'X-ID' => null,
		// Max. 20 znaků. - Identifikátor platby na straně příkazce. Jedná se o interní ID, jehož použití a interpretace závisí na bance příkazce.
		'X-URL' => null,
		// Max. 140 znaků. - URL, které je možno využít pro vlastní potřebu
	];

	/**
	 * @var array klíče QR Faktury
	 */
	private array $sid_keys = [
		'ID' => null,
		// Max. 40 znaků - Jednoznačné označení dokladu
		'DD' => null,
		// Právě 8 znaků - Datum vystavení dokladu ve formátu YYYYMMDD
		'AM' => null,
		// Max. 18 znaků - Výše celkové částky k úhradě v měně specifikované klíčem CC
		'TP' => null,
		// Právě 1 znak - Identifikace typu daňového plnění
		'TD' => null,
		// Právě 1 znak - Identifikace typu dokladu
		'SA' => null,
		// Právě 1 znak - Příznak, který rozlišuje, zda faktura obsahuje zúčtování záloh
		'MSG' => null,
		// Max. 40 znaků - Textový popis předmětu fakturace
		'ON' => null,
		// Max. 20 znaků - Číslo (označení) objednávky, k níž se vztahuje tento účetní doklad
		'VS' => null,
		// Max. 10 znaků - Variabilní symbol
		'VII' => null,
		// Max. 14 znaků - DIČ výstavce
		'INI' => null,
		// Max. 8 znaků - IČO výstavce
		'VIR' => null,
		// Max. 14 znaků - DIČ příjemce
		'INR' => null,
		// Max. 8 znaků - IČO příjemce
		'DUZP' => null,
		// Právě 8 znaků - Datum uskutečnění zdanitelného plnění ve formátu YYYYMMDD
		'DPPD' => null,
		// Právě 8 znaků - Datum povinnosti přiznat daň ve formátu YYYYMMDD
		'DT' => null,
		// Právě 8 znaků - Datum splatnosti celkové částky ve formátu YYYYMMDD
		'TB0' => null,
		// Max. 18 znaků - Částka základu daně v základní daňové sazbě v CZK včetně haléřového vyrovnání
		'T0' => null,
		// Max. 18 znaků - Částka daně v základní daňové sazbě v CZK včetně haléřového vyrovnání
		'TB1' => null,
		// Max. 18 znaků - Částka základu daně v první snížené daňové sazbě v CZK včetně haléřového vyrovnání
		'T1' => null,
		// Max. 18 znaků - Částka daně v první snížené daňové sazbě v CZK včetně haléřového vyrovnání
		'TB2' => null,
		// Max. 18 znaků - Částka základu daně ve druhé snížené daňové sazbě v CZK včetně haléřového vyrovnání
		'T2' => null,
		// Max. 18 znaků - Částka daně ve druhé snížené daňové sazbě v CZK včetně haléřového vyrovnání
		'NTB' => null,
		// Max. 18 znaků - Částka osvobozených plnění, plnění mimo předmět DPH, plnění neplátců DPH v CZK včetně haléřového vyrovnání. V případě kladné hodnoty bez znaménka, záporná hodnota se znaménkem. Znaménko vždy explicitně určuje směr toku peněz bez ohledu na jiné atributy
		'CC' => 'CZK',
		// Právě 3 znaky - Měna celkové částky. Není-li klíč v řetězci přítomen = měna je CZK
		'FX' => null,
		// Max. 18 znaků - Směnný kurz mezi CZK a měnou celkové částky
		'FXA' => null,
		// Max. 5 znaků - Počet jednotek cizí měny pro přepočet pomocí klíče FX. Není-li v řetězci klíč přítomen = 1
		'ACC' => null,
		// Max. 46 - Identifikace čísla účtu výstavce faktury, která je složena ze dvou komponent oddělených znaménkem + Tyto komponenty jsou: číslo účtu ve formátu IBAN identifikace banky ve formátu SWIFT dle ISO 9362. Druhá komponenta (SWIFT) je přitom volitelná
		'CRC32' => null,
		// Právě 8 znaků - Kontrolní součet. Hodnota vznikne výpočtem CRC32 celého řetězce (bez klíče CRC32) a převedením této číselné hodnoty do hexadecimálního zápisu.
		'X-SW' => null,
		// Max. 30 - Označení účetního software, ve kterém byl řetězec QR Faktury (faktura) vytvořen. Libovolný řetězec dle rozhodnutí výrobce účetního software. Označení by mělo být obecně unikátní a neměnné pro daný software (nebo jeho verzi).
		'X-URL' => null,
		// Max. 70 - Údaje pro získání účetních údajů (případně faktury) ve strukturovaném formátu z on-line uložiště.
	];

	/**
	 * Přepínač, zda se má generovat pouze QR Faktura
	 */
	private bool $isOnlyInvoice = false;

	/**
	 * Konstruktor nové platby.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct(?string $account = null, int | float | null $amount = null, ?string $variable = null, ?string $currency = null)
	{
		if ($account) {
			$this->setAccount($account);
		}
		if ($amount) {
			$this->setAmount($amount);
		}
		if ($variable) {
			$this->setVariableSymbol($variable);
		}
		if ($currency) {
			$this->setCurrency($currency);
		}
	}

	/**
	 * Statický konstruktor nové platby.
	 *
	 * @param null $account
	 * @param null $amount
	 * @param null $variable
	 *
	 * @return QRInvoice
	 * @throws \InvalidArgumentException
	 */
	public static function create(?string $account = null, int | float | null $amount = null, ?string $variable = null): QRInvoice
	{
		return new self($account, $amount, $variable);
	}

	/**
	 * Nastavení čísla účtu ve formátu 12-3456789012/0100.
	 */
	public function setAccount(string $account): QRInvoice
	{
		$this->spd_keys['ACC'] = $this->sid_keys['ACC'] = self::accountToIban($account);

		return $this;
	}

	/**
	 * Nastavení IBAN (+SWIFT/BIC) čísla účtu
	 */
	public function setIban(string $iban): QRInvoice
	{
		$this->spd_keys['ACC'] = $this->sid_keys['ACC'] = $iban;

		return $this;
	}

	/**
	 * Nastavení částky.
	 */
	public function setAmount(int | float $amount): QRInvoice
	{
		$this->spd_keys['AM'] = $this->sid_keys['AM'] = sprintf('%.2F', $amount);

		return $this;
	}

	/**
	 * Nastavení variabilního symbolu.
	 */
	public function setVariableSymbol(string $vs): QRInvoice
	{
		$this->spd_keys['X-VS'] = $this->sid_keys['VS'] = $vs;

		return $this;
	}

	/**
	 * Nastavení konstatního symbolu.
	 */
	public function setConstantSymbol(string $ks): QRInvoice
	{
		$this->spd_keys['X-KS'] = $ks;

		return $this;
	}

	/**
	 * Nastavení specifického symbolu.
	 *
	 * @throws QRInvoiceException
	 */
	public function setSpecificSymbol(string $ss): QRInvoice
	{
		if (mb_strlen($ss) > 10) {
			throw new QRInvoiceException('Specific symbol is longer than 10 characters');
		}
		$this->spd_keys['X-SS'] = $ss;

		return $this;
	}

	/**
	 * Nastavení zprávy pro příjemce. Z řetězce bude odstraněna diaktirika.
	 */
	public function setMessage(string $msg): QRInvoice
	{
		$this->spd_keys['MSG'] = $this->sid_keys['MSG'] = mb_substr($this->stripDiacritics($msg), 0, 60);

		return $this;
	}

	/**
	 * Nastavení jména příjemce. Z řetězce bude odstraněna diaktirika.
	 */
	public function setRecipientName(string $name): QRInvoice
	{
		$this->spd_keys['RN'] = mb_substr($this->stripDiacritics($name), 0, 35);

		return $this;
	}

	/**
	 * Nastavení data úhrady.
	 */
	public function setDueDate(DateTime $date): QrInvoice
	{
		$this->spd_keys['DT'] = $this->sid_keys['DT'] = $date->format('Ymd');

		return $this;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function setCurrency(string $cc): QRInvoice
	{
		if (!in_array($cc, self::$currencies, true)) {
			throw new \InvalidArgumentException(sprintf('Currency %s is not supported.', $cc));
		}

		$this->spd_keys['CC'] = $this->sid_keys['CC'] = $cc;

		return $this;
	}

	/**
	 * Přepínač, zda se má generovat pouze QR Faktura
	 */
	public function setIsOnlyInvoice(bool $isOnlyInvoice): QRInvoice
	{
		$this->isOnlyInvoice = $isOnlyInvoice;

		return $this;
	}

	/**
	 * Nastavení ID faktury
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setInvoiceId(string $id): QRInvoice
	{
		if (mb_strlen($id) > 40) {
			throw new QRInvoiceException('Invoice id is longer than 40 characters');
		}

		$this->sid_keys['ID'] = $id;

		return $this;
	}

	/**
	 * Nastavení data vydání faktury
	 */
	public function setInvoiceDate(DateTime $date): QrInvoice
	{
		$this->sid_keys['DD'] = $date->format('Ymd');

		return $this;
	}

	/**
	 * Nastavení typu daňového plnění
	 */
	public function setTaxPerformance(int $tp): QRInvoice
	{
		if ($tp !== 0 && $tp !== 1 && $tp !== 2) {
			throw new QRInvoiceException('Unknown tax performance ID');
		}

		$this->sid_keys['TP'] = $tp;

		return $this;
	}

	/**
	 * Nastavení identifikace typu dokladu
	 */
	public function setInvoiceDocumentType(int $td): QRInvoice
	{
		if (($td < 0 || $td > 5) && $td !== 9) {
			throw new QRInvoiceException('Unknown invoice document type ID');
		}

		$this->sid_keys['TD'] = $td;

		return $this;
	}

	/**
	 * Nastavení příznaku, který rozlišuje, zda faktura obsahuje zúčtování záloh
	 */
	public function setInvoiceIncludingDeposit(bool $sa): QRInvoice
	{
		$this->sid_keys['SA'] = (int)$sa;

		return $this;
	}

	/**
	 * Nastavení čísla (označení) objednávky, k níž se vztahuje tento účetní doklad
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setInvoiceRelatedId(string $on): QRInvoice
	{
		if (mb_strlen($on) > 20) {
			throw new QRInvoiceException('Invoice related id is longer than 20 characters');
		}

		$this->sid_keys['ON'] = $on;

		return $this;
	}

	/**
	 * Nastavení DIČ výstavce
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setCompanyTaxId(string $vii): QRInvoice
	{
		if (mb_strlen($vii) > 14) {
			throw new QRInvoiceException('Tax identification number of invoicing subject is longer than 14 characters');
		}

		$this->sid_keys['VII'] = $vii;

		return $this;
	}

	/**
	 * Nastavení IČO výstavce
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setCompanyRegistrationId(string $ini): QRInvoice
	{
		if (mb_strlen($ini) > 8) {
			throw new QRInvoiceException('Company registration number of invoicing subject is longer than 8 characters');
		}

		$this->sid_keys['INI'] = $ini;

		return $this;
	}

	/**
	 * Nastavení DIČ příjemce
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setInvoiceSubjectTaxId(string $vir): QRInvoice
	{
		if (mb_strlen($vir) > 14) {
			throw new QRInvoiceException('Tax identification number of invoiced subject is longer than 14 characters');
		}

		$this->sid_keys['VIR'] = $vir;

		return $this;
	}

	/**
	 * Nastavení IČO příjemce
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setInvoiceSubjectRegistrationId(string $inr): QRInvoice
	{
		if (mb_strlen($inr) > 8) {
			throw new QRInvoiceException('Company registration number of invoiced subject is longer than 8 characters');
		}

		$this->sid_keys['INR'] = $inr;

		return $this;
	}

	/**
	 * Nastavení data uskutečnění zdanitelného plnění
	 */
	public function setTaxDate(DateTime $date): QrInvoice
	{
		$this->sid_keys['DUZP'] = $date->format('Ymd');

		return $this;
	}

	/**
	 * Nastavení data povinnosti přiznat daň
	 */
	public function setTaxReportDate(DateTime $date): QRInvoice
	{
		$this->sid_keys['DPPD'] = $date->format('Ymd');

		return $this;
	}

	/**
	 * Nastavení částky základu daně v CZK včetně haléřového vyrovnání
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setTaxBase(float $amount, int $taxLevelId): QRInvoice
	{
		if ($taxLevelId < 0 || $taxLevelId > 2) {
			throw new QRInvoiceException('Unknown tax level ID');
		}

		$this->sid_keys['TB'.$taxLevelId] = sprintf('%.2f', $amount);

		return $this;
	}

	/**
	 * Nastavení částky daně v CZK včetně haléřového vyrovnání
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setTaxAmount(float $amount, int $taxLevelId): QRInvoice
	{
		if ($taxLevelId < 0 || $taxLevelId > 2) {
			throw new QRInvoiceException('Unknown tax level ID');
		}

		$this->sid_keys['T'.$taxLevelId] = sprintf('%.2f', $amount);

		return $this;
	}

	/**
	 * Nastavení částky osvobozených plnění, plnění mimo předmět DPH, plnění neplátců DPH v CZK včetně haléřového vyrovnání
	 */
	public function setNoTaxAmount(float $amount): QRInvoice
	{
		$this->sid_keys['NTB'] = sprintf('%.2f', $amount);

		return $this;
	}

	/**
	 * Nastavení směnného kurzu mezi CZK a měnou celkové částky
	 */
	public function setCurrencyRate(float $currencyRate): QRInvoice
	{
		$this->sid_keys['FX'] = sprintf('%.3f', $currencyRate);

		return $this;
	}

	/**
	 * Nastavení označení účetního software, ve kterém byl řetězec QR Faktury (faktura) vytvořen
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setTaxSoftware(string $taxSoftware): QRInvoice
	{
		if (mb_strlen($taxSoftware) > 30) {
			throw new QRInvoiceException('Tax software name is longer than 30 characters');
		}

		$this->sid_keys['X-SW'] = $taxSoftware;

		return $this;
	}

	/**
	 * Metoda vrátí QR Platbu nebo Fakturu s integrovanou QR Platbou jako textový řetězec.
	 */
	public function __toString(): string
	{
		$encoded_string = '';

		// QR Platba
		if ($this->isOnlyInvoice === false) {
			$chunks = ['SPD', self::SPD_VERSION];
			foreach ($this->spd_keys as $key => $value) {
				if (null === $value) {
					continue;
				}
				$chunks[] = $key.':'.$value;
			}
			$encoded_string .= implode('*', $chunks);
		}

		// QR Faktura
		if (!is_null($this->sid_keys['ID']) && !is_null($this->sid_keys['DD'])) {
			$chunks = ['SID', self::SID_VERSION];
			foreach ($this->sid_keys as $key => $value) {
				if (
					null === $value ||
					($this->isOnlyInvoice === false && (
						(isset($this->spd_keys[$key]) && $this->spd_keys[$key] === $value) ||
						(isset($this->spd_keys['X-'.$key]) && $this->spd_keys['X-'.$key] === $value)
					))
				) {
					continue;
				}
				$chunks[] = $key.':'.$value;
			}

			if ($this->isOnlyInvoice === false) {
				$encoded_string .= '*X-INV:'.implode('%2A', $chunks).'*';
			} else {
				$encoded_string .= implode('*', $chunks).'*';
			}
		}

		return $encoded_string;
	}

	/**
	 * Metoda vrátí QR kód jako HTML tag, případně jako data-uri.
	 */
	public function getQRCodeImage(bool $htmlTag = true, int $size = 300, int $margin = 10): string
	{
		$qrCode = $this->getQRCodeInstance($size, $margin);
		$writer = new QrPngWriter();
		$data = $writer->write($qrCode)->getDataUri();

		return $htmlTag
			? sprintf('<img src="%s" width="%2$d" height="%2$d" alt="QR Platba" />', $data, $size)
			: $data;
	}

	/**
	 * Uložení QR kódu do souboru.
	 *
	 * @throws \Endroid\QrCode\Exception\UnsupportedExtensionException
	 */
	public function saveQRCodeImage(?string $filename = null, string $format = 'png', int $size = 300, int $margin = 10): QRInvoice
	{
		$qrCode = $this->getQRCodeInstance($size, $margin);

		switch ($format) {
			case 'png':
				$writer = new QrPngWriter();
				break;
			case 'svg':
				$writer = new QrSvgWriter();
				break;
			case 'pdf':
				$writer = new QrPdfWriter();
				break;
			case 'eps':
				$writer = new QrEpsWriter();
				break;
			case 'bin':
				$writer = new QrBinaryWriter();
				break;
			default:
				throw new QRInvoiceException('Unknown file format');
				break;
		}

		$writer->write($qrCode)->saveToFile($filename);

		return $this;
	}

	/**
	 * Instance třídy QrCode pro libovolné úpravy (barevnost, atd.).
	 */
	public function getQRCodeInstance(int $size = 300, int $margin = 10): QrCode
	{
		return new QrCode(
			data: (string) $this,
			size: $size - ($margin * 2),
			encoding: new QrEncoding('UTF-8'),
			errorCorrectionLevel: QrErrorCorrectionLevel::Medium,
			margin: $margin,
			roundBlockSizeMode: QrRoundBlockSizeMode::Enlarge,
			foregroundColor: new QrColor(0, 0, 0, 0),
			backgroundColor: new QrColor(255, 255, 255, 0),
		);
	}

	/**
	 * Převedení čísla účtu na formát IBAN.
	 */
	public static function accountToIban(string $accountNumber): string
	{
		$accountNumber = explode('/', $accountNumber);
		$bank = $accountNumber[1];
		$pre = 0;
		$acc = 0;
		if (false === mb_strpos($accountNumber[0], '-')) {
			$acc = $accountNumber[0];
		} else {
			[$pre, $acc] = explode('-', $accountNumber[0]);
		}

		$accountPart = sprintf('%06d%010s', $pre, $acc);
		$iban = 'CZ00'.$bank.$accountPart;

		$alfa = 'A B C D E F G H I J K L M N O P Q R S T U V W X Y Z';
		$alfa = explode(' ', $alfa);
		$alfa_replace = [];
		for ($i = 1; $i < 27; ++$i) {
			$alfa_replace[] = $i + 9;
		}
		$controlegetal = str_replace(
			$alfa,
			$alfa_replace,
			mb_substr($iban, 4, mb_strlen($iban) - 4).mb_substr($iban, 0, 2).'00'
		);
		$controlegetal = 98 - (int) bcmod($controlegetal, 97);
		$iban = sprintf('CZ%02d%04d%06d%010s', $controlegetal, $bank, $pre, $acc);

		return $iban;
	}

	/**
	 * Odstranění diaktitiky.
	 */
	private function stripDiacritics(string $string): string
	{
		$string = str_replace(
			[
				'ě', 'š', 'č', 'ř', 'ž', 'ý', 'á', 'í', 'é', 'ú', 'ů',
				'ó', 'ť', 'ď', 'ľ', 'ň', 'ŕ', 'â', 'ă', 'ä', 'ĺ', 'ć',
				'ç', 'ę', 'ë', 'î', 'ń', 'ô', 'ő', 'ö', 'ů', 'ű', 'ü',
				'Ě', 'Š', 'Č', 'Ř', 'Ž', 'Ý', 'Á', 'Í', 'É', 'Ú', 'Ů',
				'Ó', 'Ť', 'Ď', 'Ľ', 'Ň', 'Ä', 'Ć', 'Ë', 'Ö', 'Ü',
			],
			[
				'e', 's', 'c', 'r', 'z', 'y', 'a', 'i', 'e', 'u', 'u',
				'o', 't', 'd', 'l', 'n', 'a', 'a', 'a', 'a', 'a', 'a',
				'c', 'e', 'e', 'i', 'n', 'o', 'o', 'o', 'u', 'u', 'u',
				'E', 'S', 'C', 'R', 'Z', 'Y', 'A', 'I', 'E', 'U', 'U',
				'O', 'T', 'D', 'L', 'N', 'A', 'C', 'E', 'O', 'U',
			],
			$string
		);

		return $string;
	}
}
