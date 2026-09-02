# QR Platba

[![Latest Stable Version](https://poser.pugx.org/miskith/qr-platba/v/stable)](https://packagist.org/packages/miskith/qr-platba)
[![Total Downloads](https://poser.pugx.org/miskith/qr-platba/downloads)](https://packagist.org/packages/miskith/qr-platba)
[![License](https://poser.pugx.org/miskith/qr-platba/license)](https://packagist.org/packages/miskith/qr-platba)
[![Build Status](https://travis-ci.com/miskith/QRInvoice.svg)](https://travis-ci.com/miskith/QRInvoice)

Knihovna pro snadné a spolehlivé generování platebních QR kódů (**QR Platba** dle standardu České bankovní asociace SPAYD) a fakturačních QR kódů (**QR Faktura** dle specifikace Komory daňových poradců ČR) v PHP.

QR platba zjednodušuje koncovému uživateli provedení příkazu k úhradě v mobilním bankovnictví, protože obsahuje veškeré platební údaje, které stačí pouze naskenovat.

### Vlastnosti knihovny:

- Generování standardního řetězce **SPAYD 1.0** i integrované či samostatné **QR Faktury** (**SID 1.0**).
- Plná typovost s podporou **PHP 8.4 Enumů** (`Format`, `Currency`, `PaymentType`, `InvoiceDocumentType`, `TaxPerformance`).
- Podpora pro **okamžité platby** (`PT:IP`).
- Podpora pro **alternativní účty příjemce** (`ALT-ACC`).
- Podpora pro **notifikace o platbě** na e-mail (`NT:E`) i SMS (`NT:P`).
- Doplňující parametry pro systémy výstavce: interní ID dokladu (`X-ID`), URL adresa (`X-URL`), perioda opakování (`X-PER`).
- Podpora pro výpočet kontrolního součtu **CRC32** z kanonického řetězce dle specifikace ČBA a KDP ČR (`$qrInvoice->setCRC32(true)`).
- Zobrazení HTML `<img>` tagu obsahujícího rovnou `data-uri` s QR kódem bez nutnosti ukládat soubor na disk (`$qrInvoice->getQRCodeImage()`).
- Získání čistého `data-uri` řetězce (`$qrInvoice->getQRCodeImage(false)`).
- Uložení do souboru v široké škále formátů: **PNG, SVG, PDF, EPS, WebP, GIF, binární** (`$qrInvoice->saveQRCodeImage()`).
- Získání instance objektu [Endroid\QrCode\QrCode](https://github.com/endroid/qr-code) pro pokročilé úpravy (`$qrInvoice->getQRCodeInstance()`).
- Podpora pro české bankovní účty (automatický převod na IBAN) i přímé zadání IBAN/BIC.
- Podpora pro měnu CZK i ostatní světové měny dle ISO 4217 pomocí `setCurrency(Currency::CZK)`.

> [!TIP]
> **Doporučení pro typovost (PHP 8.4 Enums):**  
> V PHP 8.4 důrazně doporučujeme používat nativní Enumy (`Format`, `Currency`, `PaymentType`, `InvoiceDocumentType`, `TaxPerformance`).  
> Předávání skalárních řetězců a celých čísel (např. `'png'`, `'CZK'`, `0`) je z důvodu zachování zpětné kompatibility stále podporováno, ale je označeno jako **deprecated** a v budoucí verzi knihovny bude odstraněno.

QR Platbu dnes podporují prakticky všechny tuzemské banky (např. Air Bank, Česká spořitelna, ČSOB, Fio banka, Komerční banka, mBank, MONETA Money Bank, Raiffeisenbank, UniCredit Bank, Banka Creditas a další).

### Požadavky:
- PHP ^8.4
- Rozšíření `ext-mbstring` a `ext-gd`

## Instalace pomocí Composeru

```bash
composer require miskith/qr-platba
```

---

## Příklad QR platby

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use miskith\QRInvoice\Enum\Currency;
use miskith\QRInvoice\QRInvoice;

$qrInvoice = new QRInvoice()
    ->setAccount('12-3456789012/0100')
    ->setAmount(1234.50)
    ->setVariableSymbol('2016001234')
    ->setConstantSymbol('0308')
    ->setSpecificSymbol('1234')
    ->setMessage('Toto je první QR platba.')
    ->setCurrency(Currency::CZK) // Doporučeno použít Enum Currency::CZK
    ->setDueDate(new \DateTime('+14 days'))
    ->setCRC32(true);           // Volitelný kontrolní součet CRC32 dle ČBA

echo $qrInvoice->getQRCodeImage(); // Zobrazí <img> tag s QR kódem
```

![Ukázka](readme/qrpayment.png)

Lze použít i zkrácený zápis pomocí statického konstruktoru:

```php
echo QRInvoice::create('12-3456789012/0100', 987.60, '2016001234')
    ->setMessage('QR platba je parádní!')
    ->getQRCodeImage();
```

---

## Rozšířené možnosti QR platby (standard ČBA SPAYD)

Knihovna plně podporuje veškeré volitelné atributy standardu ČBA s využitím Enumů:

```php
use miskith\QRInvoice\Enum\Currency;
use miskith\QRInvoice\Enum\PaymentType;
use miskith\QRInvoice\QRInvoice;

$qrInvoice = new QRInvoice()
    ->setAccount('12-3456789012/0100')
    ->setAmount(500.00)
    ->setCurrency(Currency::CZK)
    ->setVariableSymbol('2026001')
    ->setMessage('Platba objednávky')
    // Požadavek na okamžitou platbu (převod během několika sekund)
    ->setInstantPayment(true) // nebo ->setPaymentType(PaymentType::Instant)
    // Alternativní účty (např. pro bezplatný převod v rámci stejné banky)
    ->setAlternativeAccounts(['2501301193/2010', 'CZ5855000000001265098001+RZBCCZPP'])
    // Notifikace výstavci o odeslání platby na e-mail nebo SMS
    ->setNotificationEmail('faktury@firma.cz')
    // ->setNotificationPhone('+420777123456')
    // Interní identifikátor objednávky/faktury v systému výstavce
    ->setInternalId('OBJ-2026-001')
    // Odkaz na detail platby nebo webový portál
    ->setUrl('https://mojefirma.cz/platba/123')
    // Perioda opakované platby ve dnech (1 - 30)
    ->setRepeat(7)
    // Automatický kontrolní součet integrity
    ->setCRC32(true);
```

---

## Příklad QR faktury a platby v jednom (QR Platba+F)

Při vyplnění údajů faktury (`setInvoiceId`, `setInvoiceDate` apod.) je vytvořena platba se začleněnou QR Fakturou v atributu `X-INV`:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use miskith\QRInvoice\Enum\InvoiceDocumentType;
use miskith\QRInvoice\Enum\TaxPerformance;
use miskith\QRInvoice\QRInvoice;

$qrInvoice = QRInvoice::create('27-16060243/0300', 495.00, '012150672')
    ->setInvoiceId('012150672')
    ->setInvoiceDocumentType(InvoiceDocumentType::TaxInvoice)
    ->setDueDate(new \DateTime('2026-12-17'))
    ->setInvoiceDate(new \DateTime('2026-12-01'))
    ->setTaxDate(new \DateTime('2026-12-01'))
    ->setTaxPerformance(TaxPerformance::Standard)
    ->setCompanyTaxId('CZ60194383')
    ->setCompanyRegistrationId('60194383')
    ->setInvoiceSubjectTaxId('CZ12345678')
    ->setTaxBase(409.09, 0)
    ->setTaxAmount(85.91, 0);

echo $qrInvoice->getQRCodeImage();
```

![Ukázka](readme/qrinvoice.png)

---

## Příklad QR faktury (pouze faktura bez platby)

Pokud chcete vygenerovat pouze účetní údaje faktury bez platebního příkazu:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use miskith\QRInvoice\Enum\InvoiceDocumentType;
use miskith\QRInvoice\Enum\TaxPerformance;
use miskith\QRInvoice\QRInvoice;

$qrInvoice = new QRInvoice()
    ->setIsOnlyInvoice(true)
    ->setIban('CZ9701000000007098760287+KOMBCZPP')
    ->setAmount(61189.00)
    ->setVariableSymbol('3310001054')
    ->setInvoiceId('2001401154')
    ->setInvoiceDocumentType(InvoiceDocumentType::Other) // Enum nebo int 9
    ->setDueDate(new \DateTime('2026-04-12'))
    ->setInvoiceDate(new \DateTime('2026-04-04'))
    ->setTaxDate(new \DateTime('2026-04-04'))
    ->setTaxPerformance(TaxPerformance::Standard)       // Enum nebo int 0
    ->setCompanyTaxId('CZ25568736')
    ->setCompanyRegistrationId('25568736')
    ->setInvoiceSubjectTaxId('CZ25568736')
    ->setInvoiceSubjectRegistrationId('25568736')
    ->setMessage('Dodávka vybavení interiéru')
    ->setTaxBase(26492.70, 0)
    ->setTaxAmount(5563.47, 0)
    ->setTaxBase(25333.10, 1)
    ->setTaxAmount(3799.97, 1)
    ->setNoTaxAmount(-0.24)
    ->setInvoiceIncludingDeposit(false);

echo $qrInvoice->getQRCodeImage();
```

![Ukázka](readme/qrinvoice2.png)

---

## Export a formáty

### Uložení do souboru

Pro volbu výstupního formátu doporučujeme používat Enum `Format`:

```php
use miskith\QRInvoice\Enum\Format;

// PNG o velikosti 300x300 px (výchozí)
$qrInvoice->saveQRCodeImage('qrcode.png', Format::Png, 300);

// SVG o velikosti 200x200 px s 5 px marginem
$qrInvoice->saveQRCodeImage('qrcode.svg', Format::Svg, 200, 5);

// WebP obrázek
$qrInvoice->saveQRCodeImage('qrcode.webp', Format::Webp, 300);

// GIF obrázek
$qrInvoice->saveQRCodeImage('qrcode.gif', Format::Gif, 150);

// PDF dokument
$qrInvoice->saveQRCodeImage('qrcode.pdf', Format::Pdf, 300);

// EPS vektorový soubor
$qrInvoice->saveQRCodeImage('qrcode.eps', Format::Eps, 300);

// Binární výstup
$qrInvoice->saveQRCodeImage('qrcode.bin', Format::Binary, 300);
```

#### Přehled hodnot Enumu `Format`:
* `Format::Png`
* `Format::Svg`
* `Format::Webp`
* `Format::Gif`
* `Format::Pdf`
* `Format::Eps`
* `Format::Binary` (nebo alias `Format::Bin`)

### Zobrazení Data URI

```php
// Vrátí řetězec ve formátu: data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...
$dataUri = $qrInvoice->getQRCodeImage(false);
```

### Získání textového SPAYD / SID řetězce

```php
$spaydString = (string) $qrInvoice;
// např. "SPD*1.0*ACC:CZ0301000000123456789012*AM:1234.50*CC:CZK*X-VS:2016001234"
```

---

## Kontrolní součet CRC32

Specifikace ČBA SPAYD a KDP ČR umožňují přidat kontrolní součet `CRC32` pro ověření integrity dat:

```php
// Aktivace automatického výpočtu CRC32
$qrInvoice->setCRC32(true);

// Získání vypočteného 8místného hexadecimálního kontrolního součtu (např. "AAD80227")
$crc = $qrInvoice->getCRC32();
```

Kontrolní součet je počítán algoritmem IEEE 802.3 přes kanonickou podobu řetězce (s abecedně setříděnými atributy) přesně podle oficiální specifikace.

---

## Odkazy

- [Oficiální specifikace formátu QR Platba (ČBA)](https://qr-platba.cz/pro-vyvojare/specifikace-formatu/)
- [Oficiální web QR Faktury](https://qr-faktura.cz/)
- [Originální projekt na GitHubu](https://github.com/dfridrich/QRPlatba)
- [Balíček na Packagist.org](https://packagist.org/packages/miskith/qr-platba)

## Licence

Tento projekt je licencován pod licencí MIT - podrobnosti naleznete v přiloženém souboru [LICENSE](LICENSE).
