# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0] - 2026-09-02

### Added
- **QR Code Reader & Parser (`QRInvoice::fromString()`)**:
  - Full support for parsing Czech QR Platba (`SPD*...`), QR Faktura (`SID*...`), combined formats (`SPD` + `X-INV`), and SEPA EPC QR codes (`BCD\n002...`).
  - Added `verifyCRC32()` method to validate checksum integrity of parsed strings.
  - Comprehensive getters for all extracted data.
- **SEPA EPC QR Code standard (`EPC069-12`)**:
  - Added `Standard::Epc` support for Eurozone payments.
  - Added factory method `QRInvoice::createEpc(...)`.
  - Added support for BIC/SWIFT (`setBic`), SEPA Purpose Code (`setPurpose`), and ISO 11649 Remittance Reference (`setRemittanceReference`).
- **Pure SVG string output (`getSvg()`)**:
  - Added `getSvg()` method returning raw vector SVG markup for direct inline HTML/Latte/Blade template embedding or file saving, with an option to omit XML declaration.
- **Logo & Center Icon embedding**:
  - Added `setLogo()` to embed custom images in the center of the QR code with auto-scaling and punchout background.
  - Added `withDefaultLogo()` to instantly embed the official Czech Banking Association (ČBA) "QR Platba" badge with automatic error correction upgrade to Level High.
- **Custom Branding & Colors**:
  - Added `setForegroundColor()`, `setBackgroundColor()`, and `setColors()`.
  - Support for HEX colors (`#4F46E5`), RGB components, or Endroid `ColorInterface`.
  - Added `setTransparentBackground()` for transparent backgrounds.
- **Slovak Bank Account & IBAN support**:
  - Added `setSlovakAccount()` and factory method `QRInvoice::createSlovak()`.
  - Automatic conversion of Slovak accounts to IBAN (`SK...`).
- **Account Number Validation (Modulo 11)**:
  - Integrated weighted Modulo 11 validation according to ČNB (Czech) and NBS (Slovak) specifications (`validateCzechAccount()`, `validateSlovakAccount()`, `setValidateAccount()`).
- **Extended SPAYD & Invoice Attributes**:
  - Alternative accounts (`setAlternativeAccounts()`, `addAlternativeAccount()`).
  - Instant payment flag (`setInstantPayment()`, `createInstant()`).
  - Direct notification channels (`setNotificationEmail()`, `setNotificationPhone()`, `clearNotification()`).
  - Internal payment identifier (`setInternalId()`), custom URL (`setUrl()`), and retry repeat period (`setRepeat()`).
  - Checksum CRC32 calculation and verification (`setCRC32()`, `getCRC32()`).
- **Factory Constructors**:
  - `QRInvoice::create(...)`
  - `QRInvoice::createInstant(...)`
  - `QRInvoice::createEpc(...)`
  - `QRInvoice::createSlovak(...)`
  - `QRInvoice::createTaxInvoice(...)`
- **Native Backed Enums**:
  - Added `Currency`, `Format`, `InvoiceDocumentType`, `PaymentType`, `Standard`, `TaxPerformance`.

### Changed / Deprecations
- **PHP Requirement**: Minimum PHP version raised to **PHP 8.4+**.
- **String literals for enums deprecated**: Passing plain string literals to `setPaymentType()`, `setCurrency()`, `setTaxPerformance()`, `setInvoiceDocumentType()`, and `saveQRCodeImage()` is deprecated in favor of backed enums (backward compatibility preserved).
- **Composer schema**: Removed `version` field from `composer.json` in accordance with Packagist guidelines.

### Performance
- **Single-pass diacritics stripping (`stripDiacritics`)**: Replaced 40+ sequential `str_replace` passes with a single-pass `strtr` using a compile-time lookup table, eliminating dynamic memory allocations.
- **Direct ASCII IBAN arithmetic (`accountToIban`)**: Replaced dynamic array generation (`range()`) and string replacement with direct ASCII arithmetic `(ord(...) - 55)` for country codes.
- **Zero-allocation Modulo 11 validation**: Pre-allocated class constants for weights eliminating runtime array allocations and slicing.

### Quality Assurance & Tooling
- **PHPStan**: Static analysis configured and passing at **Level Max (Level 10)** with `phpstan-strict-rules` and `phpstan-phpunit` (0 errors).
- **Rector**: Configured with prepared code modernization sets.
- **CI / CD**: GitHub Actions workflows updated to `actions/checkout@v7` and tested on PHP 8.4 and PHP 8.5. Configured Dependabot for Composer and GitHub Actions.
