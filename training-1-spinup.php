<?php

/*
 * Global variables
 */
$verbose = FALSE;
$output_file="/tmp/" . str_replace('.php', '', basename(__FILE__));
$pid_file="/tmp/" . str_replace('.php', '', basename(__FILE__)) . "_pids.txt";
$drush = exec('which drush');
$echo = exec('which echo');
$rm = exec('which rm');
$egrep = exec('which egrep');
$site_name='ucb-train-editor';
$product_uuid='327b857f-92fe-4b29-9575-a16f807546e4';
$organization_uuid='94a63981-a120-4f67-ae63-11547dee8be1';

$usage = <<<EOT

USAGE:

php path/to/training-1-spinup.php \
  --number_sites=10 \
  --number_start_at=3 \
  -v  #turn on verbose output

EOT;

if (!is_executable($drush)) {
  print "We found your drush at:\n$drush\n...but it's not executable.";
  print "Please fix that.\n";
  exit(1);
}


$longopts  = array(
  "number_sites:",     // Required value

  "number_start_at:",     
);

$shortopts = "v::";
$options = getopt($shortopts, $longopts);

if (array_key_exists('v', $options)) {
  $verbose = TRUE;
}

if (($options['number_sites'] <= 0)) {
  print($usage);
  return;
}

//TODO: check that pauth has been run

$i=1;
$max = $options['number_sites'];

if (isset($options['number_start_at']) && $options['number_start_at'] > 0) {
  $i = $options['number_start_at'];
  $max = $max + $options['number_start_at'] - 1;
}

// Remove old $output_file and start a new one
exec("$rm $output_file" . '*');

while ($i <= $max) {
  ($i < 10) ? $I = "0$i" : $I = $i;
  $cmd = "$echo $site_name-$I | $drush pantheon-site-create $site_name-$I --product=$product_uuid --organization=$organization_uuid";
  if ($verbose) print sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $output_file . "-$site_name-$I-out.txt", $pid_file) . "\n";
  exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $output_file . "-$site_name-$I-out.txt", $pid_file));
  $i++;
}

$out = array();
//print "\nLooking for errors in $output_file*:\n\n";
print "\nPlease run:\n";
print "\negrep -i \"fail|error\" $output_file*\n";

//sleep(2);

/*
 * fails because can't display ascii codes
$cmd = "$egrep -i \"fail|error\" $output_file*";
print "$cmd\n";
exec($cmd, $out);
print implode("\n", $out);
//print_r($out);
*/
if (!file_exists($pid_file)) {
  print "File doesn't exist: $pid_file.  No processes to cancel.\n";
  exit(1);
}

// Cancel the drush processes.  Site creation will continue on the server.
$pids = explode("\n", file_get_contents($pid_file));
foreach  ($pids as $pid) {
  if (empty($pid)) continue;
  $cmd = "kill -1 $pid";
  //if ($verbose) echo "$cmd\n";
  //exec($cmd); //can cause jobs to fail. sleep longer?
}



//remove the pid file
unlink($pid_file);

?>