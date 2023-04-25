<?php

function email_exists_in_airtable($email_to_find = "") {
  # call the search function like this:

  # $email_to_find = "test@GMAIL.COM";
  # if( email_exists_in_airtable($email_to_find)) { 
      # echo "email $email_to_find exists in airtable\n";
  # } else {
      # echo "email $email_to_find does not exist in airtable\n";
  # }

  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();

  $airtable_api = $_ENV['AIRTABLE_ACCESS_TOKEN'];
  $base_id      = $_ENV['AIRTABLE_BASE_ID'];
  $table_id     = $_ENV['AIRTABLE_TABLE_ID'];

  #echo "testing a query against airtable\n";
  #echo "Looking for $email_to_find\n";

  # search for an exact match on email in airtable
  $curl_base = "https://api.airtable.com/v0/$base_id/New%20eTap%20Accounts%20%7C%202022?maxRecords=3&view=All%20New%20Accounts";
  $curl_return_fields = "fields%5B%5D=email%20address";
  $curl_filter = "filterByFormula=%7Bemail%20address%7D%20%3d%20%27$email_to_find%27";
  $curl_url = $curl_base . "&" . $curl_return_fields . "&" . $curl_filter;
  #echo "curl filter is $curl_filter\n";

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $curl_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER,
    array("Authorization: Bearer $airtable_api",
      "Content-Type: application/json"));
  #echo "executing this command:\n";
  #echo "$curl_url\n";
  #echo "that decodes to:\n";
  #echo urldecode($curl_url);
  #echo "\n";
  $result = curl_exec($ch);

  #echo "got result: $result\n";

  # parse the response

  $decoded = json_decode($result);
  #echo var_dump($decoded);
  $found = $decoded->{'records'};
  #echo var_dump($found);

  #echo "found " . count($found) . " results\n";
  if (count($found)) {
    return true;
  } else {
    return false;
  }
}

function add_to_airtable($person) {
  # convert a person object to json and add to airtable
  # create airtable record in "welcome series base: new accounts 2022 table"

  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();
  $airtable_api = $_ENV['AIRTABLE_ACCESS_TOKEN'];
  $base_id      = $_ENV['AIRTABLE_BASE_ID'];
  $table_id     = $_ENV['AIRTABLE_TABLE_ID'];
  date_default_timezone_set('America/Los_Angeles');
  $mydate = date("Y-m-d");
  $curl = "curl -X POST https://api.airtable.com/v0/$base_id/New%20eTap%20Accounts%20%7C%202022";
  $headers = "  -H \"Authorization: Bearer $airtable_api\" -H \"Content-Type: application/json\" --data \'";

  $airtable_array = array();

  $testperson = new stdClass();
  $testperson->fields = new stdClass();
  $testperson->fields->{'Last name'} = $person->lastName;
  $testperson->fields->{'First name'} = $person->shortSalutation;
  $testperson->fields->{'Date account added'} = $mydate;
  $testperson->fields->{'email address'} = $person->email;
  $testperson->fields->{'Source'}    = "API triggered by new account";

  # airtable wants the record in this format for insertion
  $airtable_array[] = ($testperson);
  # convert array to json
  $output = new stdClass();
  $output->{'records'} = $airtable_array;
  $airtable_json = (json_encode($output));

  # add to airtable
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://api.airtable.com/v0/$base_id/New%20eTap%20Accounts%20%7C%202022");
  #curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER,
    array("Authorization: Bearer $airtable_api",
     "Content-Type: application/json"));
  curl_setopt($ch, CURLOPT_POSTFIELDS, $airtable_json);
  $result = curl_exec($ch);
  curl_close($ch);
  $decode = json_decode($result, true);
  if (isset($decode['error'])){
    $myerror = $decode['error'];
    echo "We encountered an error from Airtable\n";
    echo($myerror['type'] . "\n");
    return true;
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
  return false;
}

function get_airtable_list($list,$view) {
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();
  $airtable_api = $_ENV['AIRTABLE_ACCESS_TOKEN'];
  $base_id      = $_ENV['AIRTABLE_BASE_ID'];

  $mylist = rawurlencode($list);
  $myview = rawurlencode($view);
  $max = 3;

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://api.airtable.com/v0/$base_id/$mylist?maxRecords=$max&view=$myview");
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $airtable_api"]);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($ch);
  $decode = json_decode($result);
  curl_close($ch);
  #echo ("curl output: \n");
  #var_dump($decode);
  return($decode);
}

?>
