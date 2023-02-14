<?php

use PortaApi\Config as C;
use GuzzleHttp\RequestOptions as RO;

/**
 * Sample of cofig array required to run billing wrapper with all possible options.
 * You may edit this up to your requirements and load with require() or generate 
 * it in-app.
 */
$config = [
    //
    /*
     * Mandatory server host name. Only host and port, no scheme, no API path prefix,
     * but port is possible if you use non-standart port.
     */
    C::HOST => 'billing-sip-server-name.domain',
    //
    /**
     * Optional accaount to use with billing.
     * 
     * Username is mandatory. Either password or user token required.
     * Token may be found it the user details of billing admin interface,
     * it have no expirateion instead of password
     */
    C::ACCOUNT => [
        C::LOGIN => 'username',
        C::PASSWORD => 'password',
        C::TOKEN => 'user-access-token'
    ],
    //
    /**
     * Guzzle request options to be passed to Guzzle.
     * Fill list of options is here: https://docs.guzzlephp.org/en/stable/request-options.html
     * 
     * Please DO NOT override 'http_errors' - this will compeletly destroy wrapper logic.
     * Also, do not set  'json', 'query', 'body' and other data-handling Guzzle options.
     * 
     * You mau use Gooze testing capability to mock billing server answers by add mock 
     * handler and history middleware to the options array asdescribed here:
     * https://docs.guzzlephp.org/en/stable/testing.html
     */
    C::OPTIONS => [
        //Exapmle: set request timeout to 3 sec
        RO::TIMEOUT => 3,
        //Example: switch SSL vrification off, allow self-signed certs
        RO::VERIFY => false,
    ],
    //
    /**
     * Optional, defines how much seconds before token expiration to start token 
     * refresh. default is 3600, mind default token lifetime is 2 day (172800s).
     */
    C::REFRESH_MARGIN => null,
    //
    /**
     * Optional, instructs to start token refresh process if time in seconds was 
     * passed after token issue timestamp.
     * 
     * For example, if you set 3600, access token will refresh if it older than 
     * one hour. This option made 'refresh margin" unused if set. It has no meaning 
     * if set behind of token lifetime set by server (default is 2d, 172800s).
     */
    C::TOKEN_TTL => null,
];

