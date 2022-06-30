<?php
use UnitedPrototype\GoogleAnalytics;
require_once '../includes/autoload.php'; // Update to point at your php-ga install

$GA_AccountId = 'UA-155160951-1'; // Update with your GA account
$GA_domain = 'saintlbeau.com'; // Update with your root domain
$webhookContent = '';
// Read the webhook content
$webhook = fopen('php://input' , 'rb');
while (!feof($webhook)) {
  $webhookContent .= fread($webhook, 4096);
}
fclose($webhook);

if (!empty($webhookContent)) {
  // Convert the webhook content into an array
  $shopifyOrder = json_decode($webhookContent, true);

  // START GOOGLE ANALYTICS
  $tracker = new GoogleAnalytics\Tracker($GA_AccountId, $GA_domain);

  $visitor = new GoogleAnalytics\Visitor();
  $visitor->setIpAddress($_SERVER['REMOTE_ADDR']);
  $visitor->setUserAgent($_SERVER['HTTP_USER_AGENT']);
  $visitor->setScreenResolution('1024x768');

  $session = new GoogleAnalytics\Session();

  $page = new GoogleAnalytics\Page($_SERVER['REQUEST_URI']);
  $page->setTitle('Order Cancelled');

  $tracker->trackPageview($page, $session, $visitor);

  $transaction = new GoogleAnalytics\Transaction();
  $transaction->setOrderId($shopifyOrder['name']);
  $transaction->setAffiliation('');
  $transaction->setTotal(-$shopifyOrder['total_price']);
  $transaction->setTax(-$shopifyOrder['total_tax']);
  $transaction->setShipping(-$shopifyOrder['shipping_lines'][0]['price']);
  $transaction->setCity($shopifyOrder['billing_address']['city']);
  $transaction->setRegion($shopifyOrder['billing_address']['province']);
  $transaction->setCountry($shopifyOrder['billing_address']['country']);

  foreach ( $shopifyOrder['line_items'] as $product ) {
    $item = new GoogleAnalytics\Item();
    $item->setOrderId($shopifyOrder['name']);
    $item->setSku($product['sku']);
    $item->setName($product['title']);
    $item->setVariation('');
    $item->setPrice($product['price']);
    $item->setQuantity(-$product['quantity']);
    $item->validate();
    $transaction->addItem($item);
  }
  $transaction->validate(); 

  $tracker->trackTransaction($transaction, $session, $visitor);
  // END GOOGLE ANALYTICS
}
?>
