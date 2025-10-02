<?php

#require 'airtable-libs.php';

// pull in composer dependencies for mailchimp
require_once 'vendor/autoload.php';
#include( 'vendor/drewm/mailchimp-api/src/MailChimp.php');
#include( 'vendor/drewm/mailchimp-api/src/Batch.php');
#use \DrewM\MailChimp\MailChimp;

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

#echo "Calling login method...";
$newEndpoint = $nsc->__soapCall("apiKeyLogin", array($databaseId, $apiKey));
#echo "Done\n";

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
#$categoryName = "Mailchimp and Airtable Integrations DO NOT MODIFY";
#$queryName = "New Constituents_RYAN";

$categoryName = "Janee_Key_Reports";
$queryName = "Wealth Engine Remove Select Fields Sept 9 2025";

$request = array();
$request["start"] = 0;
$request["count"] = 1;
$request["query"] = "$categoryName::$queryName";
$request["accountType"] = 0;
$request["sortOptions"] = array();
$request["clearCache"] = false;

#print_r("Exercising etapestry query for new accounts\n");
#print_r("but taking no action with mailchimp or airtable\n");
try {
  $accounts = $nsc->__soapCall("getExistingQueryResults", array($request));
} catch (Exception $e) {
  echo "Caught exception: ", $e->getMessage(), "\n";
  echo "Could not run query in E-tapestry.\n";
  echo "Does category ", $categoryName," exist?\n";
  echo "Does query ", $queryName," exist?\n";
  exit;
}

print_r("Found ".$accounts->count." eTapestry account(s)\n");
if ($debug) {
#  print_r($accounts);
}
print_r("============================================\n");

$test_etap = true;
if ($test_etap) {
  foreach ($accounts->data as $person) {
    var_dump($person);
    #print_r("Found...\n");
    print_r("id: ".$person->id."\n");
    foreach ($person->accountDefinedValues as $adv) {
            if ($adv->fieldName == "Wealth Engine Score") {
                    print_r("Wealth Engine Score: ".$adv->value."\n");
            }
    }
    foreach ($person->accountDefinedValues as $adv) {
	    if ($adv->fieldName == "Estimated Giving Capacity") {
		    print_r("Estimated Giving Capacity: ".$adv->value."\n");
	    }
    }
    foreach ($person->accountDefinedValues as $adv) {
            if ($adv->fieldName == "Total Donations") {
                    print_r("Total Donations: ".$adv->value."\n");
            }
    }
    foreach ($person->accountDefinedValues as $adv) {
            if ($adv->fieldName == "Real Estate Value") {
                    print_r("Real Estate Value: ".$adv->value."\n");
            }
    }
    print_r("firstname: ".$person->firstName."\n");
    print_r("lastname: ".$person->lastName."\n");
    print_r("\n");
    print_r("Original custom fields: \n");
    $modifyRequest = $person;
    var_dump($modifyRequest);
    print_r($modifyRequest->accountDefinedValues);
    foreach ($modifyRequest->accountDefinedValues as $key => $value) {
      if ($value->fieldName == "Wealth Engine Score") {
        $modifyRequest->accountDefinedValues[$key]->value = "";
	print_r("Found wealth engine score value in ".$key."\n");
      }
      if ($value->fieldName == "Estimated Giving Capacity") {
        $modifyRequest->accountDefinedValues[$key]->value = "";
	print_r("Found estimated giving capacity value in ".$key."\n");
      }
      if ($value->fieldName == "Real Estate Value") {
        $modifyRequest->accountDefinedValues[$key]->value = "";
	print_r("Found real estate value in ".$key."\n");
      }
    }
    print_r("Modified custom fields: \n");
    print_r($modifyRequest->accountDefinedValues);

    $modifyResult = $nsc->__soapCall("updateAccount", array($modifyRequest,false));
    if (is_soap_fault($modifyResult)) {
      trigger_error("SOAP Fault: (faultcode: {$result->faultcode}, faultstring: {$result->faultstring})", E_USER_ERROR);
      exit;
    } else {
      print_r("Modified account ".$person->id."\n");
    }
  }
}

#echo "Now logging out of etapestry\n";
// Call logout method
// stopEtapestrySession($nsc);
$nsc->__soapCall("logout", array());
#echo "Logged out\n";
?>
