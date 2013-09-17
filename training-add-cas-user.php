<?php
/*
 * Global variables
 */
$drush = exec('which drush');
$ldap_server = 'nds-test.berkeley.edu';

$usage = <<<EOT

USAGE:

php __FILE__ \
  --emails_file \
  --site_list \

EOT;

if (!is_executable($drush)) {
  print "We found your drush at:\n$drush\n...but it's not executable.";
  print "Please fix that.\n";
  exit(1);
}


$longopts  = array(
  "emails_file:",     // Required value
  "site_list:",     // Required value
);

$shortopts = "";
$options = getopt($shortopts, $longopts);

$emails = explode("\n", file_get_contents($options['emails_file']));

/*
 * look up all with one query, problem is you can't tell which ones weren't
 * found.
$j = 0;
$filter = '(|';
foreach ($emails as $email) {
  $email = trim($email);
  if (empty($email)) continue;
  $filter .= "(mail=$email)";
  $j++;
}
$filter .= ')';
*/

if (strpos($ldap_server, "-test") !== FALSE) {
  print "TEST MODE: $ldap_server\n";
}

// connect to ldap server
$ds = ldap_connect($ldap_server) or die("Could not connect to LDAP server."); // || fails

if ($ds) {
  // binding to ldap server
  $ldapbind = ldap_bind($ds); //anonymous bind
  // verify binding
  if ($ldapbind) {
    //echo "LDAP bind successful.\n";
  } else {
    echo "LDAP bind failed.\n";
  }
}
else {
  print "LDAP connect failed.\n";
}


$dn = "ou=people,dc=berkeley,dc=edu";
//$attribs = array("ou", "sn", "givenname", "mail");
$attribs = array("uid");


// lookup each email individually so we can tell which ones aren't found
$uids = array();
foreach ($emails as $email) {
  $filter = "(mail=$email)";
  $sr=ldap_search($ds, $dn, $filter, $attribs);
  $r = ldap_get_entries($ds, $sr);
  //skip blank lines in file
  if (empty($email)) continue;


  if (intval($r['count']) > 0) {
    $uids[] = $r[0]['uid'][0];
  }
  else {
    print "$email: No UID\n";
  }
}

foreach ($uids as $uid) {
  $cmd = "$drush " . $options['site_list'] . " cas-user-create $uid";

}

