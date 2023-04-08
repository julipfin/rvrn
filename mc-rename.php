<?php
# the official mailchimp library is here:
# https://github.com/mailchimp/mailchimp-marketing-php

// pull in composer dependencies for mailchimp
require_once 'vendor/autoload.php';
#include( 'vendor/drewm/mailchimp-api/src/MailChimp.php');
#use \DrewM\MailChimp\MailChimp;
require __DIR__ . '/vendor/autoload.php';
#require_once '/home/rday/git/MailchimpMarketing/vendor/autoload.php';

// dnf install php-soap

// Set login details and initial endpoint
# log in to etapestry via soap

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

# mailchimp variables
$dc      = $_ENV['MAILCHIMP_DC'];
$apikey  = $_ENV['MAILCHIMP_API_KEY'];
$list_id = $_ENV['MAILCHIMP_LIST_ID'];

$MailChimp = new MailchimpMarketing\ApiClient();
$MailChimp->setConfig([
  'apiKey' => $apikey,
  'server' => $dc
]);

$response = $MailChimp->ping->get();
print_r($response);

# get lists
#$result = $MailChimp->get('lists');
#print_r("List the lists\n");
#print_r($result);
# get the segments
#print_r("List the segments\n");
#$result = $MailChimp->get("/lists/$list_id/segments");
#print_r($result);

# get tags
#print_r("List the tags\n");
#$result = $MailChimp->get("/lists/$list_id/tag-search");
#print_r($result);
# the tag I care about is church-society

# search for members with a given first name
print_r("List members with church in first name\n");
# fields to include in response
#$fields = array('id','full_name');
$result = $MailChimp->searchMembers->search("church");
#var_dump($result);
#exit;
$members = $result->full_search->members;
#var_dump($members);
foreach ($members as $member) {
  #var_dump($member);
  $id = $member->id;
  $full_name = $member->full_name;
  $first = $member->merge_fields->FNAME;
  $last = $member->merge_fields->LNAME;
  print_r("full_name: $full_name\n");
  print_r("first: $first\n");
  print_r("last: $last\n");
  if ($last == "") {
    print_r("copying first name to last name field\n");
    $response = $MailChimp->lists->updateListMember($list_id,$id,[
      "merge_fields" => [
        "LNAME" => $first,
      ]
    ]);
  }
  if ($first == $last) {
    print_r("writing first name\n");
    $response = $MailChimp->lists->updateListMember($list_id,$id,[
      "merge_fields" => [
        "FNAME" => "Friends",
      ]
    ]);
  }
  #print_r($response);
  #exit;
}




# subscribe rday
#$result = $MailChimp->post("lists/$list_id/members", [
#  'email_address' => 'rday@linux.com',
#  'status' => 'subscribed',
#  'merge_fields' => ['FNAME'=>'Ryan', 'LNAME'=>'Day'],
#]);
#print_r($result);

#$title = $result['title'];
#$status = $result['status'];
#$detail = $result['detail'];
#
#print_r("This test should succeed with login to mailchimp and return\n");
#print_r("an error for a user that cannot be subscribed\n");
#if ($status == "400")
#  print_r("Test successful\n");
#else
#  print_r("Test failed\n");

?>
