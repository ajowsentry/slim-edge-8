# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.2]

### Changed
- Change builder from `dennisblight/sqltark` to `ajowsentry/sqltark`
- Change method call from `where` to `equals`

## [1.0.1]

### Added
- Koneksi database ke Billing Asasta (SQL Server)
- Endpoint `/billing/informasi-pelanggan`
- Endpoint `/billing/validasi-nomor-pelanggan`
- Method `responseBadRequest` di `App\Support\ResponseCreator`
- Method `responseNotFound` di `App\Support\ResponseCreator`

### Changed
- Method `responseSuccess` di `App\Support\ResponseCreator` ditambahkan parameter message

## [1.0.0]

### Added
- Konfigurasi multiple database
- Modul Sirantas
- Endpoint `sirantas/master-barang` berikut modul dan controller
- `App\Support\ResponseCreator` class sebagai helper response