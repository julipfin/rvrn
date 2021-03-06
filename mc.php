<?php

// pull in composer dependencies for mailchimp
require_once 'vendor/autoload.php';
include( 'vendor/drewm/mailchimp-api/src/MailChimp.php');
use \DrewM\MailChimp\MailChimp;
require __DIR__ . '/vendor/autoload.php';


// dnf install php-soap

// Set login details and initial endpoint
# log in to etapestry via soap

# etap variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$databaseId = $_ENV['ETAP_DATABASE_ID'];
$apiKey     = $_ENV['ETAP_API_KEY'];
#$endpoint = "https://sna.etapestry.com/v3messaging/service?WSDL";
$endpoint = "https://bos.etapestry.com/v3messaging/service?WSDL";
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
?>
