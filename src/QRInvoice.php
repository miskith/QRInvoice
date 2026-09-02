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

namespace miskith\QRInvoice;

use DateTime;
use Endroid\QrCode\Color\Color as QrColor;
use Endroid\QrCode\Color\ColorInterface;
use Endroid\QrCode\Encoding\Encoding as QrEncoding;
use Endroid\QrCode\ErrorCorrectionLevel as QrErrorCorrectionLevel;
use Endroid\QrCode\Logo\Logo as QrLogo;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode as QrRoundBlockSizeMode;
use Endroid\QrCode\Writer\BinaryWriter as QrBinaryWriter;
use Endroid\QrCode\Writer\EpsWriter as QrEpsWriter;
use Endroid\QrCode\Writer\GifWriter as QrGifWriter;
use Endroid\QrCode\Writer\PdfWriter as QrPdfWriter;
use Endroid\QrCode\Writer\PngWriter as QrPngWriter;
use Endroid\QrCode\Writer\SvgWriter as QrSvgWriter;
use Endroid\QrCode\Writer\WebPWriter as QrWebPWriter;
use miskith\QRInvoice\Enum\Currency;
use miskith\QRInvoice\Enum\Format;
use miskith\QRInvoice\Enum\InvoiceDocumentType;
use miskith\QRInvoice\Enum\PaymentType;
use miskith\QRInvoice\Enum\Standard;
use miskith\QRInvoice\Enum\TaxPerformance;

/**
 * Knihovna pro generování QR plateb v PHP.
 *
 * @see https://raw.githubusercontent.com/snoblucha/QRPlatba/master/QRPlatba.php
 */
class QRInvoice implements \Stringable
{
	/**
	 * Verze QR formátu QR Platby.
	 */
	public const string SPD_VERSION = '1.0';

	/**
	 * Verze QR formátu QR Faktury.
	 */
	public const string SID_VERSION = '1.0';

	/**
	 * Váhy pro výpočet kontrolního součtu čísla účtu modulo 11.
	 */
	private const array MODULO_11_WEIGHTS = [1, 2, 4, 8, 5, 10, 9, 7, 3, 6];

	/**
	 * Váhy pro výpočet kontrolního součtu předčíslí účtu modulo 11.
	 */
	private const array MODULO_11_PREFIX_WEIGHTS = [1, 2, 4, 8, 5, 10];

	/**
	 * Mapovací tabulka pro rychlé odstranění diakritiky v jednom průchodu.
	 */
	private const array DIACRITICS_MAP = [
		'ě' => 'e', 'š' => 's', 'č' => 'c', 'ř' => 'r', 'ž' => 'z',
		'ý' => 'y', 'á' => 'a', 'í' => 'i', 'é' => 'e', 'ú' => 'u',
		'ů' => 'u', 'ó' => 'o', 'ť' => 't', 'ď' => 'd', 'ľ' => 'l',
		'ň' => 'n', 'ŕ' => 'r', 'â' => 'a', 'ă' => 'a', 'ä' => 'a',
		'ĺ' => 'l', 'ć' => 'c', 'ç' => 'c', 'ę' => 'e', 'ë' => 'e',
		'î' => 'i', 'ń' => 'n', 'ô' => 'o', 'ő' => 'o', 'ö' => 'o',
		'ű' => 'u', 'ü' => 'u',
		'Ě' => 'E', 'Š' => 'S', 'Č' => 'C', 'Ř' => 'R', 'Ž' => 'Z',
		'Ý' => 'Y', 'Á' => 'A', 'Í' => 'I', 'É' => 'E', 'Ú' => 'U',
		'Ů' => 'U', 'Ó' => 'O', 'Ť' => 'T', 'Ď' => 'D', 'Ľ' => 'L',
		'Ň' => 'N', 'Ä' => 'A', 'Ć' => 'C', 'Ë' => 'E', 'Ö' => 'O',
		'Ü' => 'U',
	];

	/**
	 * @var array<string, string|int|bool|null> klíče QR Platby
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
	 * @var array<string, string|int|bool|null> klíče QR Faktury
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
	 * Přepínač validace čísla účtu pro tuto instanci (Modulo 11).
	 */
	private bool $validateAccount = false;

	/**
	 * Platební standard (SPAYD pro ČR, EPC pro SEPA / Eurozónu).
	 */
	private Standard $standard = Standard::Spayd;

	/**
	 * BIC / SWIFT kód banky (pro mezinárodní / SEPA EPC platby).
	 */
	private ?string $bic = null;

	/**
	 * Kód účelu platby (SEPA EPC Purpose Code, max 4 znaky).
	 */
	private ?string $purpose = null;

	/**
	 * Strukturovaná reference platby (např. ISO 11649 RF Creditor Reference).
	 */
	private ?string $remittanceReference = null;

	/**
	 * Instance loga pro umístění do středu QR kódu.
	 */
	private ?LogoInterface $logo = null;

	/**
	 * Barva modulů QR kódu (popředí).
	 */
	private ?ColorInterface $foregroundColor = null;

	/**
	 * Barva pozadí QR kódu.
	 */
	private ?ColorInterface $backgroundColor = null;

	/**
	 * Původní hodnota CRC32 načtená z textového řetězce (pokud byla přítomna).
	 */
	private ?string $parsedCrc32 = null;

	/**
	 * Konstruktor nové platby.
	 *
	 * @throws \InvalidArgumentException
	 * @throws QRInvoiceException
	 */
	public function __construct(?string $account = null, int | float | string | null $amount = null, ?string $variable = null, Currency | string | null $currency = null)
	{
		if ($account !== null && $account !== '') {
			str_contains($account, '/') ? $this->setAccount($account) : $this->setIban($account);
		}

		if ($amount !== null && $amount !== '') {
			$this->setAmount($amount);
		}

		if ($variable !== null && $variable !== '') {
			$this->setVariableSymbol($variable);
		}

		if ($currency !== null) {
			$this->setCurrency($currency);
		}
	}

	/**
	 * Statický konstruktor nové platby.
	 *
	 * @throws \InvalidArgumentException
	 * @throws QRInvoiceException
	 */
	public static function create(?string $account = null, int | float | string | null $amount = null, ?string $variable = null, Currency | string | null $currency = null): QRInvoice
	{
		return new self($account, $amount, $variable, $currency);
	}

	/**
	 * Tovární metoda pro rychlé vytvoření okamžité platby (Instant Payment - PT:IP).
	 *
	 * @param string|null $account Číslo bankovního účtu nebo IBAN
	 * @param int|float|string|null $amount Částka platby
	 * @param string|null $variable Variabilní symbol platby
	 * @param Currency|string|null $currency Měna platby (výchozí: CZK)
	 * @param string|null $message Zpráva pro příjemce
	 * @throws QRInvoiceException
	 */
	public static function createInstant(
		?string $account = null,
		int | float | string | null $amount = null,
		?string $variable = null,
		Currency | string | null $currency = null,
		?string $message = null,
	): QRInvoice {
		$qr = new self($account, $amount, $variable, $currency);
		$qr->setInstantPayment(true);

		if ($message !== null) {
			$qr->setMessage($message);
		}

		return $qr;
	}

	/**
	 * Tovární metoda pro rychlé vytvoření platby dle standardu SEPA EPC QR Code (Eurozóna).
	 *
	 * @param string $account IBAN nebo číslo účtu příjemce
	 * @param string $recipientName Jméno příjemce (povinné dle standardu SEPA EPC)
	 * @param int|float|string|null $amount Částka v EUR
	 * @param string|null $message Zpráva pro příjemce
	 * @param string|null $bic BIC / SWIFT kód banky příjemce
	 * @param string|null $variableSymbol Variabilní symbol
	 * @throws QRInvoiceException
	 */
	public static function createEpc(
		string $account,
		string $recipientName,
		int | float | string | null $amount = null,
		?string $message = null,
		?string $bic = null,
		?string $variableSymbol = null,
	): QRInvoice {
		$qr = new self();
		$qr->setStandard(Standard::Epc)
			->setRecipientName($recipientName);

		if (str_contains($account, '/')) {
			$qr->setAccount($account, str_starts_with($account, 'SK') ? 'SK' : 'CZ');
		} else {
			$qr->setIban($account);
		}

		if ($amount !== null && $amount !== '') {
			$qr->setAmount($amount);
		}

		if ($message !== null) {
			$qr->setMessage($message);
		}

		if ($bic !== null) {
			$qr->setBic($bic);
		}

		if ($variableSymbol !== null) {
			$qr->setVariableSymbol($variableSymbol);
		}

		return $qr;
	}

	/**
	 * Tovární metoda pro rychlé vytvoření platby se slovenským číslem účtu.
	 *
	 * @param string $account Slovenské číslo účtu ve formátu [předčíslí-]číslo/kód_banky
	 * @param int|float|string|null $amount Částka platby
	 * @param string|null $variable Variabilní symbol platby
	 * @param Currency|string|null $currency Měna platby (výchozí: EUR)
	 * @param string|null $message Zpráva pro příjemce
	 * @throws QRInvoiceException
	 */
	public static function createSlovak(
		string $account,
		int | float | string | null $amount = null,
		?string $variable = null,
		Currency | string | null $currency = Currency::EUR,
		?string $message = null,
	): QRInvoice {
		$qr = new self();
		$qr->setSlovakAccount($account);

		if ($amount !== null && $amount !== '') {
			$qr->setAmount($amount);
		}

		if ($variable !== null) {
			$qr->setVariableSymbol($variable);
		}

		if ($currency !== null) {
			$qr->setCurrency($currency);
		}

		if ($message !== null) {
			$qr->setMessage($message);
		}

		return $qr;
	}

	/**
	 * Tovární metoda pro rychlé vytvoření QR Faktury (daňového dokladu).
	 *
	 * @param string $account Číslo bankovního účtu nebo IBAN
	 * @param int|float|string $amount Celková částka k úhradě
	 * @param string $invoiceId Číslo faktury / evidenční číslo dokladu
	 * @param DateTime|null $dueDate Datum splatnosti
	 * @param DateTime|null $invoiceDate Datum vystavení dokladu
	 * @param DateTime|null $taxDate Datum uskutečnění zdanitelného plnění (DUZP)
	 * @param string|null $variable Variabilní symbol (pokud není zadán, použije se číselná část invoiceId)
	 * @param InvoiceDocumentType|string|null $documentType Typ dokladu (výchozí: TaxInvoice)
	 * @param TaxPerformance|int|null $taxPerformance Plnění DPH (výchozí: Standard)
	 * @param Currency|string|null $currency Měna (výchozí: CZK)
	 * @throws QRInvoiceException
	 */
	public static function createTaxInvoice(
		string $account,
		int | float | string $amount,
		string $invoiceId,
		?DateTime $dueDate = null,
		?DateTime $invoiceDate = null,
		?DateTime $taxDate = null,
		?string $variable = null,
		InvoiceDocumentType | string | null $documentType = InvoiceDocumentType::TaxInvoice,
		TaxPerformance | int | null $taxPerformance = TaxPerformance::Standard,
		Currency | string | null $currency = null,
	): QRInvoice {
		$vs = $variable;
		if ($vs === null && preg_match('/[0-9]{1,10}/', $invoiceId, $m) === 1) {
			$vs = $m[0];
		}

		$invoiceDate ??= new DateTime();
		$dueDate ??= new DateTime('+14 days');

		$qr = new self($account, $amount, $vs, $currency);
		$qr->setInvoiceId($invoiceId)
			->setInvoiceDate($invoiceDate)
			->setDueDate($dueDate);

		if ($taxDate instanceof DateTime) {
			$qr->setTaxDate($taxDate);
		}

		if ($documentType !== null) {
			$qr->setInvoiceDocumentType($documentType);
		}

		if ($taxPerformance !== null) {
			$qr->setTaxPerformance($taxPerformance);
		}

		return $qr;
	}

	/**
	 * Načte a rozparsuje textový řetězec QR Platby (SPAYD), QR Faktury (SID) nebo SEPA EPC QR platby.
	 *
	 * @throws QRInvoiceException
	 */
	public static function fromString(string $string): QRInvoice
	{
		$string = trim($string);

		if (str_starts_with($string, 'BCD')) {
			return self::parseEpcString($string);
		}

		if (str_starts_with($string, 'SPD*') || str_starts_with($string, 'SID*')) {
			return self::parseSpaydString($string);
		}

		throw new QRInvoiceException('Unsupported or invalid QR string format. Expected SPAYD, SID or EPC string.');
	}

	/**
	 * Parsování SPAYD nebo SID řetězce.
	 *
	 * @throws QRInvoiceException
	 */
	private static function parseSpaydString(string $string): QRInvoice
	{
		$qr = new self();
		$isOnlyInvoice = str_starts_with($string, 'SID*');
		if ($isOnlyInvoice) {
			$qr->setIsOnlyInvoice(true);
		}

		$chunks = explode('*', trim($string, '*'));
		if (count($chunks) < 2) {
			throw new QRInvoiceException('Invalid QR string format: missing header or version.');
		}

		$header = array_shift($chunks);
		array_shift($chunks);

		if (!in_array($header, ['SPD', 'SID'], true)) {
			throw new QRInvoiceException(sprintf('Invalid format header "%s". Expected SPD or SID.', $header));
		}

		foreach ($chunks as $chunk) {
			if ($chunk === '' || !str_contains($chunk, ':')) {
				continue;
			}

			[$key, $value] = explode(':', $chunk, 2);

			if ($key === 'CRC32') {
				$qr->parsedCrc32 = $value;
				if ($isOnlyInvoice) {
					$qr->sid_keys['CRC32'] = true;
				} else {
					$qr->spd_keys['CRC32'] = true;
				}

				continue;
			}

			if ($key === 'X-INV') {
				$invString = str_replace('%2A', '*', $value);
				$invChunks = explode('*', trim($invString, '*'));
				array_shift($invChunks); // SID
				array_shift($invChunks); // 1.0

				foreach ($invChunks as $invChunk) {
					if ($invChunk === '' || !str_contains($invChunk, ':')) {
						continue;
					}

					[$invKey, $invVal] = explode(':', $invChunk, 2);
					if ($invKey === 'CRC32') {
						$qr->sid_keys['CRC32'] = true;
					} else {
						$qr->sid_keys[$invKey] = $invVal;
					}
				}

				continue;
			}

			if ($isOnlyInvoice) {
				$qr->sid_keys[$key] = $value;
			} else {
				$qr->spd_keys[$key] = $value;
				if ($key === 'ACC') {
					$qr->sid_keys['ACC'] = $value;
				}
			}
		}

		return $qr;
	}

	/**
	 * Parsování řetězce SEPA EPC QR platby (EPC069-12).
	 *
	 * @throws QRInvoiceException
	 */
	private static function parseEpcString(string $string): QRInvoice
	{
		$lines = preg_split('/\r\n|\r|\n/', trim($string));
		if ($lines === false || !isset($lines[0], $lines[3]) || $lines[0] !== 'BCD' || $lines[3] !== 'SCT') {
			throw new QRInvoiceException('Invalid SEPA EPC string format.');
		}

		$qr = new self();
		$qr->setStandard(Standard::Epc);

		$bic = $lines[4] ?? '';
		if ($bic !== '') {
			$qr->setBic($bic);
		}

		$recipientName = $lines[5] ?? '';
		if ($recipientName !== '') {
			$qr->setRecipientName($recipientName);
		}

		$iban = $lines[6] ?? '';
		if ($iban !== '') {
			$qr->setIban($iban);
		}

		$amountStr = $lines[7] ?? '';
		if ($amountStr !== '') {
			if (str_starts_with($amountStr, 'EUR')) {
				$amount = (float) substr($amountStr, 3);
				$qr->setAmount($amount);
				$qr->setCurrency(Currency::EUR);
			} else {
				$qr->setAmount((float) $amountStr);
			}
		}

		$purpose = $lines[8] ?? '';
		if ($purpose !== '') {
			$qr->setPurpose($purpose);
		}

		$reference = $lines[9] ?? '';
		if ($reference !== '') {
			$qr->setRemittanceReference($reference);
		}

		$unstructured = $lines[10] ?? '';
		if ($unstructured !== '') {
			if (preg_match('/^VS:([0-9]{1,10})\s*(.*)$/', $unstructured, $matches) === 1) {
				$qr->setVariableSymbol($matches[1]);
				if ($matches[2] !== '') {
					$qr->setMessage($matches[2]);
				}
			} else {
				$qr->setMessage($unstructured);
			}
		}

		return $qr;
	}

	/**
	 * Ověření kontrolního součtu CRC32 z parsovaného řetězce.
	 *
	 * @return bool|null true pokud kontrolní součet odpovídá, false pokud je chybný, null pokud CRC32 v řetězci chybí
	 */
	public function verifyCRC32(): ?bool
	{
		if ($this->parsedCrc32 === null) {
			return null;
		}

		$calculated = $this->getCRC32();

		return $calculated !== null && strtoupper($this->parsedCrc32) === strtoupper($calculated);
	}

	/**
	 * Původní hodnota CRC32 načtená z řetězce (pokud byla přítomna).
	 */
	public function getParsedCRC32(): ?string
	{
		return $this->parsedCrc32;
	}

	/**
	 * Získání čísla účtu / IBAN příjemce.
	 */
	public function getAccount(): ?string
	{
		$val = $this->spd_keys['ACC'] ?? $this->sid_keys['ACC'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání IBAN čísla účtu příjemce.
	 */
	public function getIban(): ?string
	{
		return $this->getAccount();
	}

	/**
	 * Získání částky platby.
	 */
	public function getAmount(): ?float
	{
		if ($this->spd_keys['AM'] !== null) {
			return (float) $this->spd_keys['AM'];
		}

		if ($this->sid_keys['AM'] !== null) {
			return (float) $this->sid_keys['AM'];
		}

		return null;
	}

	/**
	 * Získání měny platby jako Currency Enum.
	 */
	public function getCurrency(): ?Currency
	{
		$cc = $this->getCurrencyString();

		return $cc !== null ? Currency::tryFrom($cc) : null;
	}

	/**
	 * Získání 3znakového kódu měny (např. "CZK", "EUR").
	 */
	public function getCurrencyString(): ?string
	{
		$val = $this->spd_keys['CC'] ?? $this->sid_keys['CC'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání variabilního symbolu platby.
	 */
	public function getVariableSymbol(): ?string
	{
		$val = $this->spd_keys['X-VS'] ?? $this->sid_keys['VS'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání konstantního symbolu platby.
	 */
	public function getConstantSymbol(): ?string
	{
		$val = $this->spd_keys['X-KS'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání specifického symbolu platby.
	 */
	public function getSpecificSymbol(): ?string
	{
		$val = $this->spd_keys['X-SS'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání zprávy pro příjemce.
	 */
	public function getMessage(): ?string
	{
		$val = $this->spd_keys['MSG'] ?? $this->sid_keys['MSG'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání jména příjemce.
	 */
	public function getRecipientName(): ?string
	{
		$val = $this->spd_keys['RN'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání data splatnosti platby.
	 */
	public function getDueDate(): ?DateTime
	{
		$dt = $this->spd_keys['DT'] ?? $this->sid_keys['DT'] ?? null;
		if (is_string($dt)) {
			$d = DateTime::createFromFormat('Ymd', $dt);

			return $d instanceof DateTime ? $d : null;
		}

		return null;
	}

	/**
	 * Získání typu platby jako PaymentType Enum.
	 */
	public function getPaymentType(): ?PaymentType
	{
		$pt = $this->spd_keys['PT'] ?? null;

		return is_string($pt) ? PaymentType::tryFrom($pt) : null;
	}

	/**
	 * Zjistí, zda je platba označena jako okamžitá (Instant Payment).
	 */
	public function isInstantPayment(): bool
	{
		return ($this->spd_keys['PT'] ?? null) === PaymentType::Instant->value;
	}

	/**
	 * Získání e-mailové adresy pro notifikaci o platbě.
	 */
	public function getNotificationEmail(): ?string
	{
		$nt = $this->spd_keys['NT'] ?? null;
		$nta = $this->spd_keys['NTA'] ?? null;

		return ($nt === 'E' && is_string($nta)) ? $nta : null;
	}

	/**
	 * Získání telefonního čísla pro notifikaci o platbě.
	 */
	public function getNotificationPhone(): ?string
	{
		$nt = $this->spd_keys['NT'] ?? null;
		$nta = $this->spd_keys['NTA'] ?? null;

		return ($nt === 'P' && is_string($nta)) ? $nta : null;
	}

	/**
	 * Získání interního ID platby na straně příkazce.
	 */
	public function getInternalId(): ?string
	{
		$val = $this->spd_keys['X-ID'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání URL odkazu platby.
	 */
	public function getUrl(): ?string
	{
		$val = $this->spd_keys['X-URL'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání periody opakování platby ve dnech.
	 */
	public function getRepeat(): ?int
	{
		return $this->spd_keys['X-PER'] !== null ? (int) $this->spd_keys['X-PER'] : null;
	}

	/**
	 * Získání seznamu alternativních účtů.
	 *
	 * @return array<string>
	 */
	public function getAlternativeAccounts(): array
	{
		return $this->spd_keys['ALT-ACC'] !== null ? explode(',', (string) $this->spd_keys['ALT-ACC']) : [];
	}

	/**
	 * Získání čísla faktury / označení dokladu.
	 */
	public function getInvoiceId(): ?string
	{
		$val = $this->sid_keys['ID'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání data vystavení faktury.
	 */
	public function getInvoiceDate(): ?DateTime
	{
		$dd = $this->sid_keys['DD'] ?? null;
		if (is_string($dd)) {
			$d = DateTime::createFromFormat('Ymd', $dd);

			return $d instanceof DateTime ? $d : null;
		}

		return null;
	}

	/**
	 * Získání data uskutečnění zdanitelného plnění (DUZP).
	 */
	public function getTaxDate(): ?DateTime
	{
		$duzp = $this->sid_keys['DUZP'] ?? null;
		if (is_string($duzp)) {
			$d = DateTime::createFromFormat('Ymd', $duzp);

			return $d instanceof DateTime ? $d : null;
		}

		return null;
	}

	/**
	 * Získání typu dokladu jako InvoiceDocumentType Enum.
	 */
	public function getInvoiceDocumentType(): ?InvoiceDocumentType
	{
		$td = $this->sid_keys['TD'] ?? null;

		return (is_string($td) || is_int($td)) ? InvoiceDocumentType::tryFrom((int) $td) : null;
	}

	/**
	 * Získání typu daňového plnění jako TaxPerformance Enum.
	 */
	public function getTaxPerformance(): ?TaxPerformance
	{
		$tp = $this->sid_keys['TP'] ?? null;

		return (is_int($tp) || is_string($tp)) ? TaxPerformance::tryFrom((int) $tp) : null;
	}

	/**
	 * Získání DIČ výstavce.
	 */
	public function getCompanyTaxId(): ?string
	{
		$val = $this->sid_keys['VII'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání IČO výstavce.
	 */
	public function getCompanyRegistrationId(): ?string
	{
		$val = $this->sid_keys['INI'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání DIČ příjemce faktury.
	 */
	public function getInvoiceSubjectTaxId(): ?string
	{
		$val = $this->sid_keys['VIR'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Získání IČO příjemce faktury.
	 */
	public function getInvoiceSubjectRegistrationId(): ?string
	{
		$val = $this->sid_keys['INR'] ?? null;

		return is_string($val) ? $val : null;
	}

	/**
	 * Nastavení platebního standardu (SPAYD pro ČR nebo EPC pro SEPA / Eurozónu).
	 */
	public function setStandard(Standard | string $standard): QRInvoice
	{
		$this->standard = $standard instanceof Standard ? $standard : Standard::from(strtoupper($standard));

		if ($this->standard === Standard::Epc && in_array($this->spd_keys['CC'], ['CZK', null, ''], true)) {
			$this->spd_keys['CC'] = 'EUR';
		}

		return $this;
	}

	/**
	 * Získání aktuálně nastaveného platebního standardu.
	 */
	public function getStandard(): Standard
	{
		return $this->standard;
	}

	/**
	 * Nastavení BIC / SWIFT kódu banky příjemce.
	 */
	public function setBic(?string $bic): QRInvoice
	{
		$this->bic = $bic !== null ? strtoupper(trim($bic)) : null;

		return $this;
	}

	/**
	 * Získání BIC / SWIFT kódu banky příjemce.
	 */
	public function getBic(): ?string
	{
		return $this->bic;
	}

	/**
	 * Nastavení 4znakového SEPA kódu účelu platby (Purpose Code, např. GDDS, CHAR).
	 *
	 * @throws QRInvoiceException
	 */
	public function setPurpose(?string $purpose): QRInvoice
	{
		if ($purpose !== null && mb_strlen($purpose) > 4) {
			throw new QRInvoiceException('SEPA purpose code cannot exceed 4 characters.');
		}

		$this->purpose = $purpose !== null ? strtoupper(trim($purpose)) : null;

		return $this;
	}

	/**
	 * Získání SEPA kódu účelu platby.
	 */
	public function getPurpose(): ?string
	{
		return $this->purpose;
	}

	/**
	 * Nastavení strukturované reference platby dle ISO 11649 (např. RF18539007547034).
	 *
	 * @throws QRInvoiceException
	 */
	public function setRemittanceReference(?string $reference): QRInvoice
	{
		if ($reference !== null && mb_strlen($reference) > 35) {
			throw new QRInvoiceException('SEPA remittance reference cannot exceed 35 characters.');
		}

		$this->remittanceReference = $reference !== null ? trim($reference) : null;

		return $this;
	}

	/**
	 * Získání strukturované reference platby.
	 */
	public function getRemittanceReference(): ?string
	{
		return $this->remittanceReference;
	}

	/**
	 * Nastavení loga v centru QR kódu.
	 *
	 * @param LogoInterface|string|null $logo Cesta k souboru s obrázkem nebo instance LogoInterface
	 * @param int|null $width Šířka loga v px (výchozí: 50)
	 * @param int|null $height Výška loga v px (výchozí: 50)
	 * @param bool $punchoutBackground Zda vystřihnout bílé pozadí pod logem
	 * @throws QRInvoiceException
	 */
	public function setLogo(
		LogoInterface | string | null $logo,
		?int $width = 50,
		?int $height = 50,
		bool $punchoutBackground = false,
	): QRInvoice {
		if ($logo === null) {
			$this->logo = null;

			return $this;
		}

		if ($logo instanceof LogoInterface) {
			$this->logo = $logo;

			return $this;
		}

		if (!file_exists($logo)) {
			throw new QRInvoiceException(sprintf('Logo file "%s" does not exist.', $logo));
		}

		$this->logo = new QrLogo(
			path: $logo,
			resizeToWidth: $width,
			resizeToHeight: $height,
			punchoutBackground: $punchoutBackground,
		);

		return $this;
	}

	/**
	 * Nastavení oficiálního loga ČBA „QR Platba“ do středu QR kódu.
	 *
	 * @param int $size Velikost loga v px (výchozí: 50)
	 * @throws QRInvoiceException
	 */
	public function withDefaultLogo(int $size = 50): QRInvoice
	{
		$path = __DIR__ . '/../resources/qr-platba-logo.png';

		return $this->setLogo($path, $size, $size);
	}

	/**
	 * Získání instance loga.
	 */
	public function getLogo(): ?LogoInterface
	{
		return $this->logo;
	}

	/**
	 * Nastavení barvy popředí (modulů) QR kódu.
	 *
	 * Lze předat:
	 * - HEX řetězec: např. "#4F46E5", "#FFF", "10B981"
	 * - RGB složky: setForegroundColor(79, 70, 229)
	 * - Instanci ColorInterface (např. new Color(79, 70, 229))
	 * - null pro obnovení výchozí černé barvy
	 *
	 * @param ColorInterface|string|int|null $colorOrRed HEX řetězec, ColorInterface, hodnota červené složky RGB nebo null
	 * @param int|null $green Hodnota zelené složky (pokud je první parametr int)
	 * @param int|null $blue Hodnota modré složky (pokud je první parametr int)
	 * @param int $alpha Průhlednost 0-127 (0 = neprůhledná)
	 * @throws QRInvoiceException
	 */
	public function setForegroundColor(
		ColorInterface | string | int | null $colorOrRed,
		?int $green = null,
		?int $blue = null,
		int $alpha = 0,
	): QRInvoice {
		$this->foregroundColor = $colorOrRed !== null
			? $this->resolveColor($colorOrRed, $green, $blue, $alpha)
			: null;

		return $this;
	}

	/**
	 * Získání nastavené barvy popředí.
	 */
	public function getForegroundColor(): ?ColorInterface
	{
		return $this->foregroundColor;
	}

	/**
	 * Nastavení barvy pozadí QR kódu.
	 *
	 * Lze předat:
	 * - HEX řetězec: např. "#F8FAFC", "#FFFFFF"
	 * - RGB složky: setBackgroundColor(248, 250, 252)
	 * - Instanci ColorInterface
	 * - null pro obnovení výchozí bílé barvy
	 *
	 * @param ColorInterface|string|int|null $colorOrRed HEX řetězec, ColorInterface, hodnota červené složky RGB nebo null
	 * @param int|null $green Hodnota zelené složky (pokud je první parametr int)
	 * @param int|null $blue Hodnota modré složky (pokud je první parametr int)
	 * @param int $alpha Průhlednost 0-127 (0 = neprůhledná)
	 * @throws QRInvoiceException
	 */
	public function setBackgroundColor(
		ColorInterface | string | int | null $colorOrRed,
		?int $green = null,
		?int $blue = null,
		int $alpha = 0,
	): QRInvoice {
		$this->backgroundColor = $colorOrRed !== null
			? $this->resolveColor($colorOrRed, $green, $blue, $alpha)
			: null;

		return $this;
	}

	/**
	 * Získání nastavené barvy pozadí.
	 */
	public function getBackgroundColor(): ?ColorInterface
	{
		return $this->backgroundColor;
	}

	/**
	 * Hromadné nastavení barev popředí i pozadí.
	 *
	 * @param ColorInterface|string|int $foregroundColor Barva modulů (HEX, RGB nebo ColorInterface)
	 * @param ColorInterface|string|int|null $backgroundColor Barva pozadí (HEX, RGB nebo ColorInterface)
	 * @throws QRInvoiceException
	 */
	public function setColors(
		ColorInterface | string | int $foregroundColor,
		ColorInterface | string | int | null $backgroundColor = null,
	): QRInvoice {
		$this->setForegroundColor($foregroundColor);
		if ($backgroundColor !== null) {
			$this->setBackgroundColor($backgroundColor);
		}

		return $this;
	}

	/**
	 * Nastavení průhledného pozadí QR kódu.
	 */
	public function setTransparentBackground(bool $transparent = true): QRInvoice
	{
		$this->backgroundColor = $transparent
			? new QrColor(255, 255, 255, 127)
			: new QrColor(255, 255, 255, 0);

		return $this;
	}

	/**
	 * Pomocná metoda pro převod HEX řetězce, RGB složek či ColorInterface na ColorInterface.
	 *
	 * @throws QRInvoiceException
	 */
	private function resolveColor(
		ColorInterface | string | int $colorOrRed,
		?int $green = null,
		?int $blue = null,
		int $alpha = 0,
	): ColorInterface {
		if ($colorOrRed instanceof ColorInterface) {
			return $colorOrRed;
		}

		if (is_int($colorOrRed)) {
			if ($green === null || $blue === null) {
				throw new QRInvoiceException('RGB components (green and blue) must be provided when red is an integer.');
			}

			$r = max(0, min(255, $colorOrRed));
			$g = max(0, min(255, $green));
			$b = max(0, min(255, $blue));
			$a = max(0, min(127, $alpha));

			return new QrColor($r, $g, $b, $a);
		}

		$hex = ltrim(trim($colorOrRed), '#');

		if (strlen($hex) === 3) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if (preg_match('/^[0-9a-fA-F]{6}$/', $hex) !== 1) {
			throw new QRInvoiceException(sprintf('Invalid HEX color format: "%s". Expected format like "#4F46E5" or "#FFF".', $colorOrRed));
		}

		$red = max(0, min(255, (int) hexdec(substr($hex, 0, 2))));
		$green = max(0, min(255, (int) hexdec(substr($hex, 2, 2))));
		$blue = max(0, min(255, (int) hexdec(substr($hex, 4, 2))));
		$alpha = max(0, min(127, $alpha));

		return new QrColor($red, $green, $blue, $alpha);
	}

	/**
	 * Povolení či zakázání validace čísla účtu (Modulo 11).
	 */
	public function setValidateAccount(bool $validate = true): QRInvoice
	{
		$this->validateAccount = $validate;

		return $this;
	}

	/**
	 * Zjistí, zda je aktivní validace čísla účtu.
	 */
	public function isValidateAccount(): bool
	{
		return $this->validateAccount;
	}

	/**
	 * Nastavení slovenského čísla účtu ve formátu [předčíslí-]číslo/kód_banky.
	 *
	 * @throws QRInvoiceException
	 */
	public function setSlovakAccount(string $account): QRInvoice
	{
		return $this->setAccount($account, 'SK');
	}

	/**
	 * Nastavení čísla účtu ve formátu [předčíslí-]číslo/kód_banky.
	 *
	 * @param string $account Číslo účtu
	 * @param string $country Kód země (CZ nebo SK, výchozí: CZ)
	 * @throws QRInvoiceException
	 */
	public function setAccount(string $account, string $country = 'CZ'): QRInvoice
	{
		$country = strtoupper(trim($country));
		$countryName = $country === 'SK' ? 'Slovak' : 'Czech';
		if ($this->validateAccount && !self::validateAccount($account, $country)) {
			throw new QRInvoiceException(sprintf('Account number "%s" is not a valid %s bank account (modulo 11 check failed).', $account, $countryName));
		}

		$this->spd_keys['ACC'] = $this->sid_keys['ACC'] = self::accountToIban($account, $country);

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
	 * Nastavení alternativních účtů příjemce (ALT-ACC).
	 *
	 * @param array<int, string> $accounts Pole účtů ve formátu čísla účtu (12-3456789012/0100) nebo IBAN (+BIC)
	 * @throws QRInvoiceException
	 */
	public function setAlternativeAccounts(array $accounts): QRInvoice
	{
		if (count($accounts) > 2) {
			throw new QRInvoiceException('Maximum of 2 alternative accounts is allowed.');
		}

		if ($accounts === []) {
			$this->spd_keys['ALT-ACC'] = null;

			return $this;
		}

		foreach ($accounts as $acc) {
			if ($this->validateAccount && str_contains($acc, '/') && !self::validateCzechAccount($acc)) {
				throw new QRInvoiceException(sprintf('Alternative account number "%s" is not a valid Czech bank account (modulo 11 check failed).', $acc));
			}
		}

		$ibans = array_map(static fn (string $acc): string => str_contains($acc, '/') ? self::accountToIban($acc) : $acc, $accounts);
		$altAcc = implode(',', $ibans);

		if (mb_strlen($altAcc) > 93) {
			throw new QRInvoiceException('ALT-ACC value exceeds maximum length of 93 characters.');
		}

		$this->spd_keys['ALT-ACC'] = $altAcc;

		return $this;
	}

	/**
	 * Přidání alternativního účtu příjemce (ALT-ACC).
	 *
	 * @throws QRInvoiceException
	 */
	public function addAlternativeAccount(string $account): QRInvoice
	{
		$current = $this->spd_keys['ALT-ACC'] !== null ? explode(',', (string) $this->spd_keys['ALT-ACC']) : [];
		$current[] = $account;

		return $this->setAlternativeAccounts($current);
	}

	/**
	 * Nastavení částky.
	 */
	public function setAmount(int | float | string $amount): QRInvoice
	{
		if (is_string($amount)) {
			if (!is_numeric($amount)) {
				throw new \InvalidArgumentException(sprintf('Amount "%s" is not numeric.', $amount));
			}

			$amount = (float) $amount;
		}

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
	public function setDueDate(DateTime $date): QRInvoice
	{
		$this->spd_keys['DT'] = $this->sid_keys['DT'] = $date->format('Ymd');

		return $this;
	}

	/**
	 * Požadavek na provedení platby formou okamžité platby (PT:IP).
	 */
	public function setInstantPayment(bool $instant = true): QRInvoice
	{
		$this->spd_keys['PT'] = $instant ? PaymentType::Instant->value : null;

		return $this;
	}

	/**
	 * Nastavení typu platby (PT).
	 *
	 * @param PaymentType|string|null $type Doporučeno předávat PaymentType enum. Předávání textového řetězce je deprecated a v budoucí verzi bude odstraněno.
	 * @throws QRInvoiceException
	 */
	public function setPaymentType(PaymentType | string | null $type): QRInvoice
	{
		if (is_string($type)) {
			@trigger_error('Passing string to setPaymentType() is deprecated, use miskith\QRInvoice\Enum\PaymentType enum instead.', E_USER_DEPRECATED);
		}

		$typeString = $type instanceof PaymentType ? $type->value : $type;
		if ($typeString !== null && mb_strlen($typeString) > 3) {
			throw new QRInvoiceException('Payment type (PT) cannot exceed 3 characters.');
		}

		$this->spd_keys['PT'] = $typeString;

		return $this;
	}

	/**
	 * Nastavení notifikace výstavci platby přes e-mail (NT:E).
	 *
	 * @throws QRInvoiceException
	 */
	public function setNotificationEmail(string $email): QRInvoice
	{
		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 320) {
			throw new QRInvoiceException(sprintf('Invalid notification email "%s".', $email));
		}

		$this->spd_keys['NT'] = 'E';
		$this->spd_keys['NTA'] = $email;

		return $this;
	}

	/**
	 * Nastavení notifikace výstavci platby přes SMS/telefon (NT:P).
	 *
	 * @throws QRInvoiceException
	 */
	public function setNotificationPhone(string $phone): QRInvoice
	{
		$cleanPhone = preg_replace('/\s+/', '', $phone) ?? '';
		if (preg_match('/^\+?[0-9]{9,15}$/', $cleanPhone) !== 1 || mb_strlen($cleanPhone) > 320) {
			throw new QRInvoiceException(sprintf('Invalid notification phone "%s".', $phone));
		}

		$this->spd_keys['NT'] = 'P';
		$this->spd_keys['NTA'] = $cleanPhone;

		return $this;
	}

	/**
	 * Vymazání nastavení notifikace (NT, NTA).
	 */
	public function clearNotification(): QRInvoice
	{
		$this->spd_keys['NT'] = null;
		$this->spd_keys['NTA'] = null;

		return $this;
	}

	/**
	 * Nastavení měny.
	 *
	 * @param Currency|string $cc Doporučeno předávat Currency enum. Předávání textového řetězce je deprecated a v budoucí verzi bude odstraněno.
	 * @throws \InvalidArgumentException
	 */
	public function setCurrency(Currency | string $cc): QRInvoice
	{
		if (is_string($cc)) {
			@trigger_error('Passing string to setCurrency() is deprecated, use miskith\QRInvoice\Enum\Currency enum instead.', E_USER_DEPRECATED);
		}

		$currencyCode = $cc instanceof Currency ? $cc->value : $cc;

		if (Currency::tryFrom($currencyCode) === null) {
			throw new \InvalidArgumentException(sprintf('Currency %s is not supported.', $currencyCode));
		}

		$this->spd_keys['CC'] = $this->sid_keys['CC'] = $currencyCode;

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
	 * Zjistí, zda se generuje pouze QR Faktura.
	 */
	public function isOnlyInvoice(): bool
	{
		return $this->isOnlyInvoice;
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
	public function setInvoiceDate(DateTime $date): QRInvoice
	{
		$this->sid_keys['DD'] = $date->format('Ymd');

		return $this;
	}

	/**
	 * Nastavení typu daňového plnění
	 *
	 * @param TaxPerformance|int $tp Doporučeno předávat TaxPerformance enum. Předávání celého čísla je deprecated a v budoucí verzi bude odstraněno.
	 */
	public function setTaxPerformance(TaxPerformance | int $tp): QRInvoice
	{
		if (is_int($tp)) {
			@trigger_error('Passing int to setTaxPerformance() is deprecated, use miskith\QRInvoice\Enum\TaxPerformance enum instead.', E_USER_DEPRECATED);
		}

		$tpValue = $tp instanceof TaxPerformance ? $tp->value : $tp;
		if (TaxPerformance::tryFrom($tpValue) === null) {
			throw new QRInvoiceException('Unknown tax performance ID');
		}

		$this->sid_keys['TP'] = $tpValue;

		return $this;
	}

	/**
	 * Nastavení identifikace typu dokladu
	 *
	 * @param InvoiceDocumentType|int $td Doporučeno předávat InvoiceDocumentType enum. Předávání celého čísla je deprecated a v budoucí verzi bude odstraněno.
	 */
	public function setInvoiceDocumentType(InvoiceDocumentType | int | string $td): QRInvoice
	{
		if (is_int($td)) {
			@trigger_error('Passing int to setInvoiceDocumentType() is deprecated, use miskith\QRInvoice\Enum\InvoiceDocumentType enum instead.', E_USER_DEPRECATED);
		}

		$tdValue = $td instanceof InvoiceDocumentType ? $td->value : (int) $td;
		if (InvoiceDocumentType::tryFrom($tdValue) === null) {
			throw new QRInvoiceException('Unknown invoice document type ID');
		}

		$this->sid_keys['TD'] = $tdValue;

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
	public function setTaxDate(DateTime $date): QRInvoice
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
	 * Nastavení interního identifikátoru platby v systému výstavce (X-ID).
	 *
	 * @throws QRInvoiceException
	 */
	public function setInternalId(string $id): QRInvoice
	{
		if (mb_strlen($id) > 20) {
			throw new QRInvoiceException('Internal ID (X-ID) cannot exceed 20 characters.');
		}

		$this->spd_keys['X-ID'] = $id;

		return $this;
	}

	/**
	 * Nastavení URL adresy (X-URL).
	 *
	 * @throws QRInvoiceException
	 */
	public function setUrl(string $url): QRInvoice
	{
		if (mb_strlen($url) > 140) {
			throw new QRInvoiceException('URL (X-URL) cannot exceed 140 characters.');
		}

		$this->spd_keys['X-URL'] = $this->sid_keys['X-URL'] = $url;

		return $this;
	}

	/**
	 * Nastavení periodicity opakované platby ve dnech (X-PER).
	 *
	 * @param int $days Počet dní (1 - 30)
	 * @throws QRInvoiceException
	 */
	public function setRepeat(int $days): QRInvoice
	{
		if ($days < 1 || $days > 30) {
			throw new QRInvoiceException('Repeat period (X-PER) must be between 1 and 30 days.');
		}

		$this->spd_keys['X-PER'] = (string) $days;

		return $this;
	}

	/**
	 * Nastavení kontrolního součtu CRC32.
	 * Při předání true se kontrolní součet automaticky spočítá dle specifikace.
	 */
	public function setCRC32(bool $crc32 = true): QRInvoice
	{
		$this->spd_keys['CRC32'] = $crc32 ? true : null;
		$this->sid_keys['CRC32'] = $crc32 ? true : null;

		return $this;
	}

	/**
	 * Získání vypočteného CRC32 kontrolního součtu (pokud je aktivní).
	 */
	public function getCRC32(): ?string
	{
		if ($this->isOnlyInvoice) {
			return $this->sid_keys['CRC32'] === true ? $this->calculateSidCrc32() : null;
		}

		return $this->spd_keys['CRC32'] === true ? $this->calculateSpdCrc32() : null;
	}

	/**
	 * Spočítá CRC32 kontrolní součet pro QR Platbu (SPD) dle kanonické reprezentace.
	 */
	public function calculateSpdCrc32(): string
	{
		$attributes = [];
		foreach ($this->spd_keys as $key => $value) {
			if ($key === 'CRC32' || null === $value) {
				continue;
			}

			$attributes[$key] = (string) $value;
		}

		ksort($attributes);

		$chunks = ['SPD', self::SPD_VERSION];
		foreach ($attributes as $key => $value) {
			$chunks[] = $key . ':' . $value;
		}

		return strtoupper(sprintf('%08x', crc32(implode('*', $chunks))));
	}

	/**
	 * Spočítá CRC32 kontrolní součet pro QR Fakturu (SID) dle kanonické reprezentace.
	 */
	public function calculateSidCrc32(): string
	{
		$attributes = [];
		foreach ($this->sid_keys as $key => $value) {
			if (
				$key === 'CRC32' ||
				null === $value ||
				($this->isOnlyInvoice === false && (
					(isset($this->spd_keys[$key]) && $this->spd_keys[$key] === $value) ||
					(isset($this->spd_keys['X-' . $key]) && $this->spd_keys['X-' . $key] === $value)
				))
			) {
				continue;
			}

			$attributes[$key] = (string) $value;
		}

		ksort($attributes);

		$canonical = 'SID*' . self::SID_VERSION . '*';
		foreach ($attributes as $key => $value) {
			$canonical .= $key . ':' . $value . '*';
		}

		return strtoupper(sprintf('%08x', crc32($canonical)));
	}

	/**
	 * Generování řetězce dle standardu SEPA EPC QR Code (EPC069-12 pro Eurozónu).
	 *
	 * @throws QRInvoiceException
	 */
	public function getEpcString(): string
	{
		$rawAccount = is_string($this->spd_keys['ACC']) ? $this->spd_keys['ACC'] : '';
		if ($rawAccount === '') {
			throw new QRInvoiceException('IBAN or bank account is required for SEPA EPC QR code.');
		}

		$iban = $rawAccount;
		$bic = $this->bic ?? '';

		if (str_contains($rawAccount, '+')) {
			[$iban, $accBic] = explode('+', $rawAccount, 2);
			if ($bic === '') {
				$bic = $accBic;
			}
		}

		$recipientName = is_string($this->spd_keys['RN']) ? $this->spd_keys['RN'] : '';
		if ($recipientName === '') {
			throw new QRInvoiceException('Recipient name (setRecipientName) is required for SEPA EPC QR code.');
		}

		$currency = $this->spd_keys['CC'] ?? 'EUR';
		if ($currency !== 'EUR') {
			throw new QRInvoiceException('SEPA EPC QR code requires EUR currency.');
		}

		$amountStr = '';
		if ($this->spd_keys['AM'] !== null && (float) $this->spd_keys['AM'] > 0) {
			$amountStr = sprintf('EUR%.2f', (float) $this->spd_keys['AM']);
		}

		$purpose = $this->purpose ?? '';
		$ref = $this->remittanceReference ?? '';
		$unstructured = '';

		if ($ref === '') {
			$msg = is_string($this->spd_keys['MSG']) ? $this->spd_keys['MSG'] : '';
			$vs = is_string($this->spd_keys['X-VS']) ? $this->spd_keys['X-VS'] : '';
			if ($vs !== '' && $msg !== '') {
				$unstructured = 'VS:' . $vs . ' ' . $msg;
			} elseif ($vs !== '') {
				$unstructured = 'VS:' . $vs;
			} else {
				$unstructured = $msg;
			}
		}

		$lines = [
			'BCD',
			'002',
			'1',
			'SCT',
			$bic,
			mb_substr($recipientName, 0, 70),
			$iban,
			$amountStr,
			mb_substr($purpose, 0, 4),
			mb_substr($ref, 0, 35),
			mb_substr($unstructured, 0, 140),
		];

		while ($lines !== [] && end($lines) === '') {
			array_pop($lines);
		}

		return implode("\n", $lines);
	}

	/**
	 * Metoda vrátí QR Platbu nebo Fakturu s integrovanou QR Platbou jako textový řetězec.
	 */
	public function __toString(): string
	{
		if ($this->standard === Standard::Epc) {
			return $this->getEpcString();
		}

		$encoded_string = '';

		// QR Platba
		if ($this->isOnlyInvoice === false) {
			$chunks = ['SPD', self::SPD_VERSION];
			foreach ($this->spd_keys as $key => $value) {
				if ($key === 'CRC32' || null === $value) {
					continue;
				}

				$chunks[] = $key . ':' . $value;
			}

			if ($this->spd_keys['CRC32'] === true) {
				$chunks[] = 'CRC32:' . $this->calculateSpdCrc32();
			}

			$encoded_string .= implode('*', $chunks);
		}

		// QR Faktura
		if ($this->sid_keys['ID'] !== null && $this->sid_keys['DD'] !== null) {
			$chunks = ['SID', self::SID_VERSION];
			foreach ($this->sid_keys as $key => $value) {
				if (
					$key === 'CRC32' ||
					null === $value ||
					($this->isOnlyInvoice === false && (
						(isset($this->spd_keys[$key]) && $this->spd_keys[$key] === $value) ||
						(isset($this->spd_keys['X-' . $key]) && $this->spd_keys['X-' . $key] === $value)
					))
				) {
					continue;
				}

				$chunks[] = $key . ':' . $value;
			}

			if ($this->sid_keys['CRC32'] === true) {
				$chunks[] = 'CRC32:' . $this->calculateSidCrc32();
			}

			if ($this->isOnlyInvoice === false) {
				$encoded_string .= '*X-INV:' . implode('%2A', $chunks) . '*';
			} else {
				$encoded_string .= implode('*', $chunks) . '*';
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
		$data = $writer->write($qrCode, $this->logo)->getDataUri();

		return $htmlTag
			? sprintf('<img src="%s" width="%2$d" height="%2$d" alt="QR Platba" />', $data, $size)
			: $data;
	}

	/**
	 * Metoda vrátí čistý vektorový SVG řetězec pro přímé vložení do šablony (HTML/Latte/Blade) nebo uložení.
	 *
	 * @param int $size Velikost v px (výchozí: 300)
	 * @param int $margin Okraj v px (výchozí: 10)
	 * @param bool $excludeXmlDeclaration Zda vynechat hlavičku <?xml version="1.0"?> pro přímé inline HTML vložení (výchozí: false)
	 */
	public function getSvg(int $size = 300, int $margin = 10, bool $excludeXmlDeclaration = false): string
	{
		$qrCode = $this->getQRCodeInstance($size, $margin);
		$writer = new QrSvgWriter();

		return $writer->write(
			$qrCode,
			$this->logo,
			options: [
				QrSvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => $excludeXmlDeclaration,
			],
		)->getString();
	}

	/**
	 * Uložení QR kódu do souboru.
	 *
	 * @param string|null $filename Cesta k cílovému souboru
	 * @param Format|string $format Doporučeno předávat Format enum. Předávání textového řetězce je deprecated a v budoucí verzi bude odstraněno.
	 * @param int $size Velikost v px (výchozí: 300)
	 * @param int $margin Okraj v px (výchozí: 10)
	 * @throws QRInvoiceException
	 * @throws \Exception
	 */
	public function saveQRCodeImage(?string $filename = null, Format | string $format = Format::Png, int $size = 300, int $margin = 10): QRInvoice
	{
		if (is_string($format)) {
			@trigger_error('Passing string to saveQRCodeImage() is deprecated, use miskith\QRInvoice\Enum\Format enum instead.', E_USER_DEPRECATED);
		}

		$qrCode = $this->getQRCodeInstance($size, $margin);

		$formatStr = $format instanceof Format ? $format->value : strtolower($format);

		$writer = match ($formatStr) {
			'png' => new QrPngWriter(),
			'svg' => new QrSvgWriter(),
			'pdf' => new QrPdfWriter(),
			'eps' => new QrEpsWriter(),
			'bin', 'binary' => new QrBinaryWriter(),
			'webp' => new QrWebPWriter(),
			'gif' => new QrGifWriter(),
			default => throw new QRInvoiceException('Unknown file format'),
		};

		$path = $filename ?? ('qrcode.' . $formatStr);
		$writer->write($qrCode, $this->logo)->saveToFile($path);

		return $this;
	}

	/**
	 * Instance třídy QrCode pro libovolné úpravy (barevnost, atd.).
	 */
	public function getQRCodeInstance(int $size = 300, int $margin = 10): QrCode
	{
		$errorCorrection = $this->logo instanceof LogoInterface ? QrErrorCorrectionLevel::High : QrErrorCorrectionLevel::Medium;

		return new QrCode(
			data: (string) $this,
			encoding: new QrEncoding('UTF-8'),
			errorCorrectionLevel: $errorCorrection,
			size: $size - ($margin * 2),
			margin: $margin,
			roundBlockSizeMode: QrRoundBlockSizeMode::Enlarge,
			foregroundColor: $this->foregroundColor ?? new QrColor(0, 0, 0, 0),
			backgroundColor: $this->backgroundColor ?? new QrColor(255, 255, 255, 0),
		);
	}

	/**
	 * Ověří, zda číslo účtu splňuje pravidla formátu a váženého Modulo 11 pro danou zemi (CZ nebo SK).
	 */
	public static function validateAccount(string $accountNumber, string $country = 'CZ'): bool
	{
		if (preg_match('/^(?:([0-9]{1,6})-)?([0-9]{2,10})\/([0-9]{4})$/', trim($accountNumber), $matches) !== 1) {
			return false;
		}

		$prefix = $matches[1];
		$account = $matches[2];

		if (!self::checkModulo11($account, self::MODULO_11_WEIGHTS)) {
			return false;
		}

		if ($prefix !== '' && (int) $prefix !== 0) {
			if (!self::checkModulo11($prefix, self::MODULO_11_PREFIX_WEIGHTS)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Ověří, zda české číslo účtu splňuje pravidla ČNB (formát a vážené Modulo 11).
	 */
	public static function validateCzechAccount(string $accountNumber): bool
	{
		return self::validateAccount($accountNumber, 'CZ');
	}

	/**
	 * Ověří, zda slovenské číslo účtu splňuje pravidla NBS (formát a vážené Modulo 11).
	 */
	public static function validateSlovakAccount(string $accountNumber): bool
	{
		return self::validateAccount($accountNumber, 'SK');
	}

	/**
	 * Výpočet váženého kontrolního součtu modulo 11 zprava doleva dle specifikace ČNB / NBS.
	 *
	 * @param array<int> $weights
	 */
	public static function checkModulo11(string $number, array $weights): bool
	{
		$len = strlen($number);
		$sum = 0;
		for ($i = 0; $i < $len; ++$i) {
			$digit = (int) $number[$len - 1 - $i];
			$sum += $digit * $weights[$i];
		}

		return ($sum % 11) === 0;
	}

	/**
	 * Převedení čísla účtu na formát IBAN (CZ nebo SK).
	 *
	 * @param string $accountNumber Číslo účtu ve formátu [předčíslí-]číslo/kód_banky
	 * @param string $country Kód země (CZ nebo SK, výchozí CZ)
	 */
	public static function accountToIban(string $accountNumber, string $country = 'CZ'): string
	{
		$country = strtoupper(trim($country));
		$accountParts = explode('/', $accountNumber);
		$bank = $accountParts[1] ?? '';
		$pre = '0';
		$acc = $accountParts[0];
		if (str_contains($accountParts[0], '-')) {
			[$pre, $acc] = explode('-', $accountParts[0]);
		}

		$accountPart = sprintf('%06d%010s', (int) $pre, $acc);
		$countryNumeric = (ord($country[0]) - 55) . (ord($country[1]) - 55);
		$numericString = $bank . $accountPart . $countryNumeric . '00';
		$checkDigits = 98 - (int) bcmod($numericString, '97');

		return sprintf('%s%02d%04d%06d%010s', $country, $checkDigits, (int) $bank, (int) $pre, $acc);
	}

	/**
	 * Odstranění diakritiky v jednom průchodu.
	 */
	private function stripDiacritics(string $string): string
	{
		return strtr($string, self::DIACRITICS_MAP);
	}
}
