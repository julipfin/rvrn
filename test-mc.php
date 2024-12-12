<?php

// pull in composer dependencies for mailchimp
require_once 'vendor/autoload.php';
include( 'vendor/drewm/mailchimp-api/src/MailChimp.php');
use \DrewM\MailChimp\MailChimp;
require __DIR__ . '/vendor/autoload.php';


// dnf install php-soap

// Set login details and initial endpoint
# log in to etapestry via soap

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

# etap variables
#$databaseId = $_ENV['ETAP_DATABASE_ID'];
#$apiKey     = $_ENV['ETAP_API_KEY'];
#$endpoint = "https://sna.etapestry.com/v3messaging/service?WSDL";
#$endpoint = "https://bos.etapestry.com/v3messaging/service?WSDL";

# mailchimp variables
$dc      = $_ENV['MAILCHIMP_DC'];
$apikey  = $_ENV['MAILCHIMP_API_KEY'];
$list_id = $_ENV['MAILCHIMP_LIST_ID'];

$MailChimp = new MailChimp($apikey);
# get lists
#$result = $MailChimp->get('lists');
# subscribe rday
$result = $MailChimp->post("lists/$list_id/members", [
  'email_address' => 'rday@linux.com',
  'status' => 'subscribed',
  'merge_fields' => ['FNAME'=>'Ryan', 'LNAME'=>'Day'],
]);
print_r($result);

print_r("\n");
print_r($MailChimp->getLastError());
print_r("\n");
$title = $result['title'];
$status = $result['status'];
$detail = $result['detail'];

if ($status == "401") {
  print_r("Error: Unauthorized. Bad API key?\n");
}

print_r("This test should succeed with login to mailchimp and return\n");
print_r("an error for a user that cannot be subscribed\n");
if ($status == "400")
  print_r("Test successful\n");
else
  print_r("Test failed\n");

?>
