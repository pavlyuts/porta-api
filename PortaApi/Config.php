<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApi;

/**
 * Class to define config and other constants
 *
 */
class Config {

    /**
     * Hostmane of billing API server, only host/IP and optional port, not prefixes, 
     * not path after unless you hava API out of standart location
     */
    const HOST = 'host';

    /**
     * Array with account data, should include **login** and either of **password**
     * or **token**
     */
    const ACCOUNT = 'account';

    /**
     * Login to auth with billing
     */
    const LOGIN = 'login';

    /**
     * Password to auth with biling. User's API token may used, instead of password 
     * the token never expires 
     */
    const PASSWORD = 'password';

    /**
     * Token to auth the user with billling. You may setup token for a user with 
     * billing interface. Token never expire.
     */
    const TOKEN = 'token';

    /**
     * Options to handle http request, passed to Guzzle as is.
     * 
     * Be careful and consult Guzzle docs: https://docs.guzzlephp.org/en/stable/request-options.html
     */
    const OPTIONS = 'options';

    /**
     * Seconds left to token expire considered need to refresh token. 
     * 
     * Defaullt value is 3600s.
     */
    const REFRESH_MARGIN = 'refresh_margin';
    
    /**
     * If set, enforce token time-to-life to the given time in seconds from issue 
     * label, then trigger token refresh. Default is none set.
     */
    const TOKEN_TTL = 'token_ttl';

    /**
     * datetime format string for use on the billing API
     */
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    
    /**
     * Portabilling main API edpoints base
     */
    const API_BASE = '/rest';

    /**
     * Portabilling ESPF API edpoints base
     */
    const ESPF_BASE = '/espf/v1';
    
    /**
     * Key for main API method params array
     */
    const PARAMS = 'params';

}
