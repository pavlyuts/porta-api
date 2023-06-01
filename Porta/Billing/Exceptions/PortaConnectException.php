<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace Porta\Billing\Exceptions;

/**
 * Exception to throw on network and HTTP protcol errors
 *
 * As API/ESPF also used non-200 code to rport errors, this exception only thrown when
 * the returned code/error is not a part of API/ESPF specification.
 *
 * @api
 * @package Exceptions
 */
class PortaConnectException extends PortaException {

}
