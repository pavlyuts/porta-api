<?php

/*
 * PortaOne Billing JSON API wrapper usage example
 */
require __DIR__ . '/../../vendor/autoload.php';

use Porta\Billing\Billing;
use Porta\Billing\Config;
use Porta\Billing\Session\SessionFileStorage;
use Porta\Billing\AsyncOperation;

// Setup persistent session storage
$storage = new SessionFileStorage('/tmp/portaone_session');

// Create config object with host and account
$config = new Config(
        'my-porta-one-server.com',
        [Config::LOGIN => 'myLogin', Config::PASSWORD => 'myPass']
);

// Create the wrapper
$billing = new Billing($config, $storage);

//Load first 50 customers from the server
$t = microtime(true);
$answer = $billing->call('/Customer/get_customer_list', ['limit' => 50]);
$customers = $answer['customer_list'];

echo "Loaded " . count($customers) . " customer records in " . (microtime(true) - $t) . " seconds\n";

// remove account from config object and re-create taking sesson data from ctorage
$config->getAccount();
$billing = new Billing($config, $storage);

// Preapare bulk load of their accounts
/** @var AsyncOperation[] $requests */
$requests = [];
foreach ($customers as $customer) {
    $customerId = $customer['i_customer'];
    $requests[$customerId] = new AsyncOperation('Account/get_account_list', ['i_customer' => $customerId]);
}

// Bulk load of accounts
$t = microtime(true);
$billing->callAsync($requests);
echo "Complete " . count($requests) . " calls in " . (microtime(true) - $t) . " seconds\n";

//Print out results
foreach ($customers as $customer) {
    /** @var AsyncOperation $request */
    $request = $requests[$customer['i_customer']];

    echo "Customer '{$customer['name']}' has ";
    if (!$request->success()) {
        echo "error loading account data: " . $request->getException()->getMessage() . "\n";
        continue;
    }
    $accounts = $request->getResponse()['account_list'];
    echo count($accounts) . " accounts.\n";
    foreach ($accounts as $account) {
        echo "    Account ID: " . $account['id'] . "\n";
    }
}

// That's all folks!


