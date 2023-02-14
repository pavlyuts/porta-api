# PortaOne Billing API and ESPF wrapper for PHP

## Purpose

This package intended to simplify communication to PortaOne billing system while creating user portal applications, integration and provisioning code. Build for composer with PSR-4 autoload.

Classes:
- **[Billing](https://github.com/pavlyuts/porta-api/wiki/Billing)** class wrapping PortaBilling API. Used to create, read, change and remove objects in the billing system.
- **[ESPF](https://github.com/pavlyuts/porta-api/wiki/ESPF)** class wrapping ESPF API

Please, refer [project Wiki](https://github.com/pavlyuts/porta-api/wiki) for details and usage example.

## Installation
In the Composer storage. Just add proper require section:

    "require": {
        "pavlyuts/porta-api": "^0.1"
    }
It is a good idea to fix the version you use. Don't use next version without review, I can't promose backward compatibility even will try to keep it. Please, review the [changelog](https://github.com/pavlyuts/porta-api/blob/master/CHANGELOG.MD) before to change used version.

## Dependencies
- php: ^7.2
- guzzlehttp/guzzle: ^7.0

## PortaOne documentation
Your API documentatnin is always available on your PortaBilling SIP server, same host as billing admin interface, at `/doc/api/` path.

Please, also refer to [PortaOne documentation](https://docs.portaone.com/). Mind your release!

## Testing
Tested with PHPUnit 8.5 on php 7.2 and 7.4, up to 100% coverage, which does not mean everything will work as intended.

To test, install in dev mode and use `composer test` command from library root.

Manualy tested and used with PortaBilling release MR100.