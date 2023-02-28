<?php

require 'airtable-libs.php';

// pull in composer dependencies for mailchimp
require_once 'vendor/autoload.php';
include( 'vendor/drewm/mailchimp-api/src/MailChimp.php');
include( 'vendor/drewm/mailchimp-api/src/Batch.php');
use \DrewM\MailChimp\MailChimp;

// pull in dependencies for etapestry/soap
// dnf install php-soap

// Set login details and initial endpoint
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$databaseId = $_ENV['ETAP_DATABASE_ID'];
$apiKey     = $_ENV['ETAP_API_KEY'];
$endpoint = "https://sna.etapestry.com/v3messaging/service?WSDL";
#$endpoint = "https://bos.etapestry.com/v3messaging/service?WSDL";
$debug=false;

// Instantiate SoapClient

#echo "Establishing Soap Client...";
$nsc = new SoapClient($endpoint);
#echo "Done\n";

if (is_soap_fault($nsc)) {
  trigger_error("SOAP Fault: (faultcode: {$result->faultcode}, faultstring: {$result->faultstring})", E_USER_ERROR);
  exit;
}

// Invoke login method

try {
  #echo "Calling login method...";
  $newEndpoint = $nsc->__soapCall("apiKeyLogin", array($databaseId, $apiKey));
  #echo "Done\n";
} catch (Exception $e) {
  echo "Caught exception: ", $e->getMessage(), "\n";
  echo "Could not log in to E-tapestry.  Giving up.\n";
  exit;
}

// Did we login?
if (is_soap_fault($newEndpoint)) {
  trigger_error("SOAP Fault: (faultcode: {$result->faultcode}, faultstring: {$result->faultstring})", E_USER_ERROR);
  exit;
}

// Determine if the login method returned a value...this will occur
// when the database you are trying to access is located at a different
// environment that can only be accessed using the provided endpoint
if ($newEndpoint != "")
{
  #echo "New Endpoint: $newEndpoint<br><br>\n";
  // Instantiate SoapClient with different endpoint
  #echo "Establishing Soap Client with new endpoint...";
  $nsc = new SoapClient($newEndpoint);
  #echo "Done\n";

  // Invoke login method
  #echo "Calling login method...";
  $nsc->__soapCall("apiKeyLogin", array($databaseId, $apiKey));
  #echo "Done\n";

  if (is_soap_fault($nsc)) {
    trigger_error("SOAP Fault: (faultcode: {$result->faultcode}, faultstring: {$result->faultstring})", E_USER_ERROR);
    exit;
  }
}

// Output results
#echo "Login to etapestry successful\n";

// get etap campaign list
$get_campaign_list = false;
if ($get_campaign_list) {
  $includeDisabled = false;
  $results = $nsc->__soapCall("getCampaigns", array($includeDisabled));
  print_r($results);
  print_r("==================================\n");
}

// run a query
// the query must be defined in etapestry
$categoryName = "MailChimp Integration DO NOT MODIFY";
$queryName = "New Constituents_RYAN";
$request = array();
$request["start"] = 0;
$request["count"] = 200;
$request["query"] = "$categoryName::$queryName";
$request["accountType"] = 0;
$request["sortOptions"] = array();
$request["clearCache"] = false;

#print_r("Run etapestry query for new accounts\n");
$accounts = $nsc->__soapCall("getExistingQueryResults", array($request));

print_r("Found ".$accounts->count." new eTapestry account(s)\n");
if ($debug) {
  print_r($accounts);
}
print_r("============================================\n");

// api docs for mailchimp
// https://mailchimp.com/developer/marketing/api/ping/ping/
// test mailchimp health
$dc      = $_ENV['MAILCHIMP_DC'];
$apikey  = $_ENV['MAILCHIMP_API_KEY'];
$list_id = $_ENV['MAILCHIMP_LIST_ID'];

$MailChimp = new MailChimp($apikey);
# get lists
#$result = $MailChimp->get('lists');
#print_r($result);

$enroll_with_mailchimp = true;
if ($enroll_with_mailchimp) {
  foreach ($accounts->data as $person) {
    #print_r("Subscribing...\n");
    #print_r("firstname: ".$person->firstName."\n");
    #print_r("lastname: ".$person->lastName."\n");
    #print_r("email: ".$person->email."\n");
    #print_r("\n");
    if ($person->email == "") { 
      print_r("Not enrolling ".$person->sortName." due to missing email\n");
      continue;
    }
    $mc_result = $MailChimp->post("lists/$list_id/members", [
      'email_address' => $person->email,
      'status' => 'subscribed',
      'merge_fields' => 
        ['FNAME'  => $person->firstName, 
         'LNAME'  => $person->lastName,
         'MMERGE3'=> $person->id],
      ]);
    if ($MailChimp->success()) {
      #print_r($mc_result);
      print_r("Enrolled ".$person->email." in Mail Chimp.\n");
      if (email_exists_in_airtable($person->email)) {
        print_r("Email ".$person->email." is already in airtable.\n");
      } else {
        print_r("Adding email ".$person->email." to airtable.\n");
        add_to_airtable($person);
      } 
    } else {
      #echo $MailChimp->getLastError();
      print_r("Already enrolled ".$person->email." in mailchimp; not added to airtable\n");
    }
  }
}

#echo "Now logging out of etapestry\n";
// Call logout method
// stopEtapestrySession($nsc);
$nsc->__soapCall("logout", array());
#echo "Logged out\n";
?>
