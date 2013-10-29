<?php
/*
 * Global variables
 */
$verbose = TRUE;
$output_file = "/tmp/" . str_replace('.php', '', basename(__FILE__));
$pid_file = "/tmp/" . str_replace('.php', '', basename(__FILE__)) . "_pids.txt";
$drush = exec('which drush');
$rm = exec('which rm');
$site_name = 'ucb-train-editor';
$thisfile = __FILE__;
$usage = <<<EOT
USAGE:

php $thisfile \
  --start=50 #site number to start at
  --end=51   #site number to end at
  -i         #(optionally) run install
  -p         #provide admin password for the site. (Required if -i is present)

EOT;

if (!is_executable($drush)) {
  print "We found your drush at:\n$drush\n...but it's not executable.";
  print "Please fix that.\n";
  exit(1);
}

/*
 * Functions
 */
function yesno($question, $boolean = FALSE) {
  echo $question . " (y/n): ";
  $handle = fopen("php://stdin", "r");
  $line = fgets($handle);
  if (strtolower(substr($line, 0, 1)) != 'y') {
    echo "You said 'no'.\n";
    if ($boolean) {
      return FALSE;
    }
    else {
      exit(0);
    }
  }
  if ($boolean) {
    return TRUE;
  }
  else {
    echo "\nContinuing...\n";
  }
}

$longopts = array(
  "start:", // Required value
  "end:",
);

$shortopts = "ip:";
$options = getopt($shortopts, $longopts);


if ($options['start'] > 1) {
  $i = $options['start'];
}
else {
  $i = 1;
}

if ((array_key_exists('i', $options)) && (!array_key_exists('p', $options))) {
  print $usage;
  print "\nIf you specify -i (install Drupal), you must specify -p [SOME PASSWORD].\n";
  exit(1);
}

// Remove old $output_file and start a new one
exec("$rm $output_file" . '*');
//reset the pid file
if (file_exists($pid_file)) {
  unlink($pid_file);
}


if (yesno("Shall we refresh your Pantheon drush alisases? (Did you just spin up new sites?)", TRUE)) {
  exec("$drush pantheon-aliases");
}

while ($i <= $options['end']) {
  ($i < 10) ? $I = "0$i" : $I = $i;

  if (!array_key_exists('i', $options)) {
    // set the connection mode to sftp for every site
    $cmd = "$drush psite-cmode $site_name-$I dev sftp";

    if ($verbose) {
      print "$cmd\n";
    }
    exec($cmd);
  }
  else {
    $cmd = "$drush -y @pantheon.$site_name-$I.dev site-install ucb_start --site-mail=training110@berkeley.edu --site-name=\"UCB Editor Training $I\" --account-mail=training110@berkeley.edu --account-name=admin --account-pass=" . $options['p'] . " update_status_module='array(FALSE,FALSE)'";
    //background this command to make the script run faster
    if ($verbose) {
      print sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $output_file . "-$site_name-$I-out.txt", $pid_file) . "\n";
    }
    exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $output_file . "-$site_name-$I-out.txt", $pid_file));
    /*
    if ($verbose) print "$cmd\n";

    exec($cmd);
    */
  }
  $i++;
}

/*<
print "Waiting for connection mode to switch to SFTP.../n";
sleep(30);
///waiting a long time doesn't help.

*/