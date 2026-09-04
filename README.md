# EBANX Assignment

A REST API to simulate basic bank transactions — checking balance, depositing, withdrawing, and transferring between accounts.

Built with **PHP** using **Slim Framework** for routing, **PHPUnit** for unit tests, and **APCu** for in-memory storage.

## Requirements

- FrankenPHP - or any PHP 8.x with the APCu extension
- Composer

## Setup

Install dependencies:

```bash
composer install
```

## Running the server

```bash
frankenphp php-server --listen localhost:8080 -root ./public
```

## Running tests

```bash
frankenphp php-cli vendor/bin/phpunit tests/
```
> Make sure ```apc.enable_cli = 1``` is set in your php.ini — APCu is disabled in CLI mode by default.


