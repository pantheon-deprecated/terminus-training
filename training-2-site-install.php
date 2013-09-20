<?php
/*
 * Global variables
 */
$verbose = true;
$output_file="/tmp/" . str_replace('.php', '', basename(__FILE__));
$pid_file="/tmp/" . str_replace('.php', '', basename(__FILE__)) . "_pids.txt";
$drush = exec('which drush');
$rm = exec('which rm');
$site_name='ucb-train-editor';

$usage = <<<EOT

USAGE:

php __FILE__ \
  --start
  --end
  -i   <-- run install
EOT;

if (!is_executable($drush)) {
  print "We found your drush at:\n$drush\n...but it's not executable.";
  print "Please fix that.\n";
  exit(1);
}


$longopts  = array(
  "start:",     // Required value
  "end:",
);

$shortopts = "i::";
$options = getopt($shortopts, $longopts);




if ($options['start'] > 1) {
  $i = $options['start'];
}
else {
  $i = 1;
}

// Remove old $output_file and start a new one
exec("$rm $output_file" . '*');
//reset the pid file
if (file_exists($pid_file)) {
  unlink($pid_file);
}


while ($i <= $options['end']) {
  ($i < 10) ? $I = "0$i" : $I = $i;

   if (!array_key_exists('i', $options)) {
     // set the connection mode to sftp for every site
     $cmd = "$drush psite-cmode $site_name-$I dev sftp";

     if ($verbose) print "$cmd\n";
     exec($cmd);
   }
  else {
    $cmd = "$drush -y @pantheon.$site_name-$I.dev site-install ucb_start --site-mail=training110@berkeley.edu --site-name=\"UCB Editor Training $I\" --account-mail=training110@berkeley.edu --account-name=admin --account-pass='28Uhall*' update_status_module='array(FALSE,FALSE)'";
    //background this command to make the script run faster
    if ($verbose) print sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $output_file . "-$site_name-$I-out.txt", $pid_file) . "\n";
    exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $output_file . "-$site_name-$I-out.txt", $pid_file));
    /*
    if ($verbose) print "$cmd\n";

    exec($cmd);
    */
  }
  $i++;
}

/*
print "Waiting for connection mode to switch to SFTP.../n";
sleep(30);
///waiting a long time doesn't help.


//reset the counter
if ($options['start'] > 1) {
  $i = $options['start'];
}
else {
  $i = 1;
}

while ($i <= $options['end']) {
  ($i < 10) ? $I = "0$i" : $I = $i;
  //install the sites in loop (vs. using a site list so that you can set
  //numbered site name
  $cmd = "$drush -y @pantheon.$site_name-$I.dev site-install ucb_start --site-mail=training110@berkeley.edu --site-name=\"UCB Editor Training $I\" --account-mail=training110@berkeley.edu --account-name=admin --account-pass='28Uhall*' update_status_module='array(FALSE,FALSE)'";
  //background this command to make the script run faster
  //$bgcmd = "$cmd > $output_file-$I-out.txt 2>&1 & echo $! >> $pid_file";
  //if ($verbose) print "$bgcmd\n";
  if ($verbose) print "$cmd\n";
  //exec($bgcmd);
  exec($cmd);
  $i++;
}
*/
/*
print "\nPlease run:\n";
print "\negrep -i \"fail|error|Installation complete\" $output_file*\n";

*/