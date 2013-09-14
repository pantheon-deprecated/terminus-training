<?php

/*
 * Global variables
 */
$drush = exec('which drush');
$site_name='ucb-train-editor';
$product_uuid='327b857f-92fe-4b29-9575-a16f807546e4';

$usage = <<<EOT

USAGE:

php path/to/training-1-spinup.php \
  --number_sites=10 \
  --number_start_at=3 \

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

$shortopts = "";
$options = getopt($shortopts, $longopts);


if (($options['number_sites'] <= 0)) {
  print($usage);
  return;
}

//TODO: check that pauth has been run

$i=1;
$max = $options['number_sites'];

if (isset($options['number_start_at']) && $options['number_start_at'] > 0) {
  $i = $options['number_start_at'];
  $max = $max + $options['number_start_at'];
}

while ($i <= $max) {
  ($i < 10) ? $I = "0$i" : $I = $i;
  $cmd = "$drush pantheon-site-create $site_name-$I --product=$product_uuid";
  echo "$cmd \n";
  $i++;
}

?>