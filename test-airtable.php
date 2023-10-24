<?php

require __DIR__ . '/vendor/autoload.php';
require "airtable-libs.php";

$testdata = array();
$testperson = new stdClass();
$testperson->fields = new stdClass();
$testperson->fields->{'Last name'} = "Day";
$testperson->fields->{'First name'} = "Ryan";
$testperson->fields->{'Date account added'} = "";
$testperson->fields->{'email address'} = "rday@finninday.net";
$testperson->fields->{'Source'} = "";
$testdata[] = ($testperson);
$testperson = new stdClass();
$testperson->fields = new stdClass();
$testperson->fields->{'Last name'} = "Day";
$testperson->fields->{'First name'} = "Julie";
$testperson->fields->{'Date account added'} = "";
$testperson->fields->{'email address'} = "julie@finninday.net";
$testperson->fields->{'Source'} = "";
$testdata[] = ($testperson);

#echo ("Test data is constructed\n");

#print_r($testdata);
#exit();

$add_airtable_record = false;
if ($add_airtable_record) {

  foreach ($testdata as $person) {
    if (email_exists_in_airtable($person->email)) {
      print_r("Email ".$person->email." is already in airtable.\n");
    } else {
      print_r("Trying to add ".$person-email." ... ");
      $error = add_to_airtable($person);
      if ($error) {
        echo("Failed to add to airtable\n");
      } else {
        echo("Added record to airtable\n");
      }
    }
  }
}

$get_list = true;
if ($get_list) {
  $list = "New eTap Accounts | 2022";
  $view = "All New Accounts";
  echo("Asking airtable for list: $list, view: $view\n");
  $result = get_airtable_list($list,$view);
  echo ("Got some results\n");
   var_dump($result);
  if (property_exists($result, 'error')) {
    echo ("got an error\n");
    echo ("it says this:\n");
    var_dump($result->{"error"});
  } else {
    if (property_exists($result, 'records')) {
      echo ("count of results: ".count($result->{"records"})."\n");
      foreach ($result->{"records"} as $person) {
        echo ("email: ".$person->{"fields"}->{"email address"}."\n");
      }
    }
  }
}

$handle_airtable_response = false;
if ($handle_airtable_response) {
  $error = '{"error":{"type":"UNKNOWN_FIELD_NAME","message":"Unknown field name: \"Last Name\""}}';
  $success = '{"records":[{"id":"rec16zCN0v6OYXvLc","createdTime":"2022-06-16T01:01:16.000Z","fields":{"Last name":"Day","First name":"Ryan","Date account added":"2022-06-16","email address":"rday@finninday.net","Source":"API triggered by new account"}},{"id":"recg1sma5flkGN5bD","createdTime":"2022-06-16T01:01:16.000Z","fields":{"Last name":"Day","First name":"Julie","Date account added":"2022-06-16","email address":"julie@finninday.net","Source":"API triggered by new account"}}]}';
  #$result = $success;
  $result = $error;
  $decode = json_decode($result, true);
  $myerror = $decode['error'];
  if ($myerror) {
    echo "We encountered an error from Airtable\n";
    echo($myerror['type'] . "\n");
  }
  $records = $decode['records'];
  if ($records) {
    echo "These records were added to Airtable\n";
    foreach($records as $record) {
      echo( 
        $record['id'] . ' ' .
        $record['createdTime'] . ' ' .
        $record['fields']['First name'] . ' ' .
        $record['fields']['Last name'] . ' ' .
        $record['fields']['email address'] . ' ' .
        $record['fields']['Source']);
      echo("\n");
    }
  }
}
?>
