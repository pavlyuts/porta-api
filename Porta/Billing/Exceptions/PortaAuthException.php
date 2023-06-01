<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Exceptions;

use Porta\Billing\Interfaces\ConfigInterface as C;

/**
 * Exception to throw on authentification errors
 *
 * @api
 * @package Exceptions
 */
class PortaAuthException extends PortaException {

    public static function createWithAccount(array $account = []) {
        return new PortaAuthException("Login failed with "
                . "login '" . ($account[C::LOGIN] ?? 'null') . "', "
                . "password '" . ($account[C::PASSWORD] ?? 'null') . "', "
                . "token '" . ($account[C::TOKEN] ?? 'null') . "'"
        );
    }

    public function __construct(string $message = "Billing API authentification error") {
        return parent::__construct($message, 500);
    }

}
