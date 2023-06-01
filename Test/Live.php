<?php

/*
 * PortaOne Billing JSON API wrapper
 * API docs: https://docs.portaone.com
 * (c) Alexey Pavlyuts <alexey@pavlyuts.ru>
 */

namespace PortaApiTest;

use Porta\Billing\Billing;
use GuzzleHttp\RequestOptions as RO;

/**
 * Description of LiveTest
 *
 */
class Live extends \PHPUnit\Framework\TestCase {

    protected static $billing;

    const CONFIG_TEMPLATE = "<?php\n\n"
            . "use Porta\Billing\Config as C;\n"
            . "use GuzzleHttp\RequestOptions as RO;\n\n"
            . "\$testConfig = [\n"
            . "    C::HOST => 'billing-sip-host.dom',\n"
            . "    C::ACCOUNT => [\n"
            . "        C::LOGIN => 'userName',\n"
            . "        C::PASSWORD => 'userPass',\n"
            . "        //Uncomment TOKEN and comment PASSWORD if you wish to use token"
            . "        //C::TOKEN => 'userToken',\n"
            . "    ],\n"
            . "    C::OPTIONS => [\n"
            . "        RO::VERIFY => false,\n"
            . "    ]\n"
            . "];\n";
    const CONFIG_FILE = __DIR__ . '/temp/live-test-config.php';
    const NO_CONFIG_MESSAGE = "Live test need config file with host and account\nConfig file created from template at:\n" . self::CONFIG_FILE
            . "\nPlease fill the template with host and account data and run live test again.\n"
            . "Do not forget to remove the file with credentials after test finished!";

    public static function setUpBeforeClass(): void {
        if (!file_exists(self::CONFIG_FILE)) {
            file_put_contents(self::CONFIG_FILE, self::CONFIG_TEMPLATE);
            static::fail(self::NO_CONFIG_MESSAGE);
        }
        require self::CONFIG_FILE;
        if ($testConfig['host'] == 'billing-sip-host.dom' || $testConfig['account']['login'] == 'userName') {
            static::fail(self::NO_CONFIG_MESSAGE);
        }
        try {
            self::$billing = new Billing(new \Porta\Billing\PortaConfig($testConfig['host'], $testConfig['account'], $testConfig['options']));
        } catch (\Porta\Billing\Exceptions\PortaException $ex) {
            self::fail("Can't init billing class with provied config.\nError: {$ex->getMessage()}");
        }
    }

    public function testGetCustomers() {
        $result = self::$billing->call('Customer/get_customer_list', ['limit' => 50]);
        $this->assertArrayHasKey('customer_list', $result);
        return $result['customer_list'];
    }

    /**
     * @depends testGetCustomers
     */
    public function testGetCustomerInfo($customerList) {
        $list = [];
        foreach ($customerList as $customer) {
            $list[] = new \Porta\Billing\AsyncOperation('Customer/get_customer_info', ['i_customer' => $customer['i_customer']]);
        }
        self::$billing->callAsync($list);
        foreach ($list as $item) {
            if (!$item->success()) {
                throw $item->getException();
            }
            $this->assertTrue($item->success());
            $this->assertArrayHasKey('customer_info', $item->getResponse());
        }
    }

    public function testGetCountries() {
        $result = self::$billing->call('Generic/get_countries_list', []);
        $this->assertArrayHasKey('countries_list', $result);
    }

    public function testLogout() {
        self::$billing->logout();
        $this->assertFalse(self::$billing->isSessionUp());
    }

}
