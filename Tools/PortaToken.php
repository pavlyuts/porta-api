<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest\Tools;

use Firebase\JWT\JWT;
use Porta\Billing\Components\BillingBase;

/**
 * Test tool to crate and manage JWT tokens
 *
 */
class PortaToken {

    const TOKEN_TEMPLATE = [
        "exp" => 1675945147,
        "i_user" => 5,
        "i_env" => 1,
        "login" => "userName",
        "scopes" => "espf.api:write espf.handlers:write",
        "iat" => 1675772347,
        "jti" => "2cfc62c22529547256913502b270cc3c",
        "realm" => "admin",
        "aud" => [
            "portabilling-api",
            "portasip-api",
            "espf-api"
        ],
        "is_super_user" => 0
    ];
    const SESSION_TEMPLATE = [
        "session_id" => "2cfc62c22529547256913502b270cc3c",
        "refresh_token" => "829d5f9cbaf5d5053daac92df2b51709",
        "expires_at" => "2023-02-09 12:19:07",
        "access_token" => "eyJhbGciOiJSUzI1NiJ9.eyJleHAiOjE2NzU5NDUxNDcsImlfdXNlciI6NSwiaV9lbnYiOjEsImxvZ2luIjoicGF2bHl1dHMiLCJzY29wZXMiOiJlc3BmLmFwaTp3cml0ZSBlc3BmLmhhbmRsZXJzOndyaXRlIiwiaWF0IjoxNjc1NzcyMzQ3LCJqdGkiOiIyY2ZjNjJjMjI1Mjk1NDcyNTY5MTM1MDJiMjcwY2MzYyIsInJlYWxtIjoiYWRtaW4iLCJhdWQiOlsicG9ydGFiaWxsaW5nLWFwaSIsInBvcnRhc2lwLWFwaSIsImVzcGYtYXBpIl0sImlzX3N1cGVyX3VzZXIiOjB9.LinANPh1NyKgzhMIuMF3h_xuAGZqLDAH8ftNyNhH8W-VEPL_IfyyA6hhetHLaowRLgf7iY4Z92VV0qhOq2gAGV1oes4SfiUstuDBrCRvCkqZOukAEO6tg85RUQ65JK5GH3seukU19Z8j7ydveBQhlWhnKUMb5e3N2CdRy7JrHdBhp-u3VZQmpkHI2OG8jxoVC6Fy39k4yMW8aikNCU3Fq2C6YN3POeJKM2ttXMMrhwLH7PsXp79UvfzxC84HrfJhGL70nsVizGuk_swduGATYLKW-Gwt9OIxJFM3LeTyVUQesZHZUPbAdv2-7sOP4RciOZpJFd5cJKiWqlMWlGbelw"
    ];
    const PRIV_KEY = <<< EOT
-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEA2i5IbnUP3713cvKn3ZP1b0xxL/eicv8eJbY/8eKg2qDU6HxR
gNZ43hPnVKR3qCXbbpnUxVv+r7Zfqjlx8CzJAGA3RIPds8yubAS1UGGS23eINCvd
JFS1Ub1o+KBk00NswVWJsLPHpYGIlNnc5k39W/f6D1u5qG7JDQ5e/mtM7zuGDyws
5o1hxt1/qaKcSLOcEBLF+owI1RaRAAFmqjz2FPXODiepGpkFYIW4cuKZg19Comk7
de9r/9rnGlyiir1w5rHyF2bY2+lAu8W33Amxml33i+HkyX2wxc4M9geanaYxLwta
O3jWS1oFhupIZuCzlN+SOKvRnnD0rteisN5j+QIDAQABAoIBAQDVIWk4HbqIAflx
nIFG/oY/VxkqlmlmlLjKdiI5E/22FG5nPSoRBXHE3wSXtqH87B5TCIrE0H0XALyq
+LrIt7cSWusiTv5/6W5prp/ACdD/+uBetoqsNuN5GeI1HdngVnki04BR0Q0yLDtT
zQ7xGzZoZPEtl5jhZHeZ/XPmbMMW3ezsKfAIKIH8xTdfWlbDHpx9mjUVvnnlTQkc
NuIxnflW9XsvelfamNO3QIQGs0siJoSxcuYEAdYnXXxVXTTLORczVZG+c5k8U843
hXFLdFyGPUM2bNC+AJxM0eKXutE+TVAGziXP+8dmLeudNXnhKkyahwFmfeoVZdcj
d/xs2ZMRAoGBAO+KHidoNhendPqrJmqBVNRxJnPMA+TvYasH4JxVAQ/cKvLBEmiF
NjYY+8xGwXKy1QjHl7CDbUYArZ629AaV2RMXfKW9ci9ZdTL1t2FreyaOriVeBu3B
WBSQyH0UYRqzwZn07KT7flz657oMicouCYdqIzRfOKo96c22BXSwGYqfAoGBAOks
blezDu+BgkFAwvbAW+LgG6O1XxUeacaxq42pcm4xDjgResJc/CDm0OlHyZ6gnMC5
iNj9xPufCEmbyOwnYXg7QFizDvmlFA7Ahem76yuX0+6STcfJjZWgoJqbjf3OSpTg
E00U+jyGgRlaaiAtRoG0dE81P3Sm04a2eiz1waJnAoGBALRwDyzSFEUXMEgOmn6J
87OflD6QBLL0G1cxNOGuKoGe8H8yLsKq7d4sTahf+CKFUXIunzYomiysIBy5ZfJ7
+Cuoeo2CujuuoFkFvOBWjUrLGaUuQfvgs4+yTEPkEQ2DMKffVk8k3tf9bIa6ISU6
LpVhvykZPV8IClGZ9lwituqzAoGBANYnL4SueLYyLQ5/S1DTJNE/YUM/Df/Yee+6
OESYbveTaGrIawXd3tbdBtxqSVu+SZmcDXq1v9gVnMf2I1f5Z0TErnmIouVX3w8Z
dSRRqlDUVhpUFsm6bKYS685ztbp4X/lRv4hZDubN5f4CE1xQGOSBdx1UW15o5fdg
2t82K0xbAoGAfxjv0Z4KIhSgk1E0metAEJKxYioo3rMU16NaJ0g5mt9stsntbNqz
MjT09UXFc4TVp+KUtpaibDTmFs/sIRLm2pqRF65cDHatpVVp4oWtKR3eZjkGmnju
UDJt/lFtVCEy9nbHiRFkWSHESdmrgii/aAZ+LdO8rDnClGhbgxcXE0I=
-----END RSA PRIVATE KEY-----
EOT;
    const PUB_KEY = <<< EOT
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2i5IbnUP3713cvKn3ZP1
b0xxL/eicv8eJbY/8eKg2qDU6HxRgNZ43hPnVKR3qCXbbpnUxVv+r7Zfqjlx8CzJ
AGA3RIPds8yubAS1UGGS23eINCvdJFS1Ub1o+KBk00NswVWJsLPHpYGIlNnc5k39
W/f6D1u5qG7JDQ5e/mtM7zuGDyws5o1hxt1/qaKcSLOcEBLF+owI1RaRAAFmqjz2
FPXODiepGpkFYIW4cuKZg19Comk7de9r/9rnGlyiir1w5rHyF2bY2+lAu8W33Amx
ml33i+HkyX2wxc4M9geanaYxLwtaO3jWS1oFhupIZuCzlN+SOKvRnnD0rteisN5j
+QIDAQAB
-----END PUBLIC KEY-----
EOT;

    /**
     * Creates billing-like session record on login
     *
     * @param int $expireShift - time before expired, negative to put in the past
     */
    public static function createLoginData(int $expireShift = 0) {
        $t = (new \DateTime('now', new \DateTimeZone('UTC')))->setTimestamp(time() + $expireShift);
        $session = self::SESSION_TEMPLATE;
        $session['expires_at'] = BillingBase::timeToBilling($t);
        $session['access_token'] = self::createJWT($t);
        return $session;
    }

    /**
     * Creates billing-like session data on token refresh
     *
     * @param int $expireShift - time before expired, negative to put in the past
     */
    public static function createRefreshData(int $expireShift = 0) {
        $session = self::createLoginData($expireShift);
        unset($session['session_id']);
        return $session;
    }

    public static function createJWT(\DateTime $expires) {
        $content = self::TOKEN_TEMPLATE;
        $content['exp'] = $expires->getTimestamp();
        $content['iat'] = $expires->getTimestamp() - 172800;
        return JWT::encode($content, self::PRIV_KEY, 'RS256');
    }

}
