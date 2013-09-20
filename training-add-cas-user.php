<?php
/*
 * Global variables
 */
$verbose = true;
$drush_dir = $_SERVER['HOME'] . '/.drush';
$tmp_dir = $_SERVER['HOME'] . '/tmp';
$drush = exec('which drush');
$ldap_server = 'nds-test.berkeley.edu';

//always add these admins
$default_admins = array(
  '213108', //bwood
  '1043991', // instructor helen nishikai
  '18493', // Kathleen Valerio
  '212372', // test-212372
  '212373', // test-212373
  '212374', // test-212374
);
/*
 * Functions
 */
function yesno($question) {
  echo $question . " (y/n): ";
  $handle = fopen ("php://stdin","r");
  $line = fgets($handle);
  if (strtolower(substr($line, 0, 1)) != 'y') {
    echo "Aborting.\n";
    exit(0);
  }
  echo "\n";
  echo "Continuing...\n";
}

$usage = <<<EOT

USAGE:

php __FILE__ \
  --emails_file=/home/bwood/tmp/emails.txt \
  --site_list=train-editor \

(site list will be expanded to ~/.drush/train-editor.aliases.drushrc.php)
EOT;

if (!is_executable($drush)) {
  print "We found your drush at:\n$drush\n...but it's not executable.";
  print "Please fix that.\n";
  exit(1);
}

if (!is_writable($tmp_dir)) {
  print "Need writable directory at $tmp_dir\n";
  exit(1);
}

$longopts  = array(
  "emails_file:",     // Required value
  "site_list:",     // Required value
);

$shortopts = "";
$options = getopt($shortopts, $longopts);


if (empty($options['emails_file']) || empty($options['site_list'])) {
  print "Missing required options:\n";
  print $usage . "\n";
  exit(1);
}


$site_list_file = "$drush_dir/" . $options['site_list'] . ".aliases.drushrc.php";
if (!file_exists($site_list_file)) {
  print "File missing: $site_list_file";
  exit(1);
}

if (!file_exists($options['emails_file'])) {
  print "File missing: " . $options['emails_file'] . "\n";
  exit(1);
}

$emails = explode("\n", file_get_contents($options['emails_file']));

yesno("Have you updated your aliases with the training sites (drush paliases)?");

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
$missing = false;
foreach ($emails as $email) {
  //skip blank lines in file
  if (empty($email)) continue;

  $filter = "(mail=$email)";
  $sr=ldap_search($ds, $dn, $filter, $attribs);
  $r = ldap_get_entries($ds, $sr);

  if (intval($r['count']) > 0) {
    $uids[] = $r[0]['uid'][0];
  }
  else {
    $missing = true;
    $h = fopen("$tmp_dir/emails_missing_uids.txt", "w");
    fwrite($h, $email . "\n");
    print "$email: No UID\n";
  }
}

if ($missing) {
  print "Couldn't find some uids. Problem emails saved to $tmp_dir/emails_missing_uids.txt\n";
  fclose($h);
}
else {
  print "Found uids for all emails. Yea!\n";
}



yesno("Shall we add the uids we found (plus default_admins) to the sites?");

$uids = array_merge($default_admins, $uids);

$out = array();
foreach ($uids as $uid) {
  $cmd = "$drush -y @" . $options['site_list'] . " cas-user-create $uid";
  if ($verbose) print "$cmd\n";
  exec($cmd, $out);
}

$out_file = "$tmp_dir/" . __FILE__ ."_out.txt";
$h = fopen($out_file, "w");
fwrite($h, implode("\n". $out_file));
fclose($h);
print "See $out_file\n";




