training_pantheon
=================
These scripts setup training sites on Pantheon for UC Berkeley Drupal classes.

Setup
-----
1. Install [Terminus](https://github.com/pantheon-systems/terminus).
2. Git clone/fork this repository
   

How we use these scripts
------------------------
1. Authenticate via terminus before running scripts. If you don't have a terminus session, the scrips will error.
2. Spin up new sites on Pantheon

   ```bash
   php training-1-spinup.php --number_sites=2 --number_start_at=50
   ```
  This script spins up multiple site with numbered names from a (hardcoded) product (upstream repository for a drupal distribution) and associates the site with a Pantheon organization (the UC Berkeley organization is hardcoded).

  Drush commands are exec'd and backgrounded.  Each PID is written to a file and then killed (kill -1).  The spin-up process will continue on the pantheon host after the local process is killed.

  *TODO*: [Replace kill -l with --no_poll](https://github.com/ucb-ist-drupal/training_pantheon/issues/1)
3. Change the Pantheon connection mode and install drupal

   ```bash
   php training-2-cmode-install.php --start=50 --end=51 -i -p big-seKreT
   ```
  Once the spinups have finished (wait for emails) run training-2
  script to change the connection mode of the sites and (optionally
  (-i)) install Drupal by running the install profile of the
  product/upstream that was defined in training-1.  This script also
  prompts you to refresh your Pantheon drush aliases--you will need to
  if you just ran training-1*.

  *TODO*: [Better notification of spin-up completion](https://github.com/ucb-ist-drupal/training_pantheon/issues/2)
4. As a prerequisite to step 5, we create backups of every site.  This
way we can restore the sites to their post-training2 state and simply
do the add administrators step when we are preparing for the next
training.

  *TODO*: Create a script for this.
  *TODO*: [Notification of Drupal install completion](https://github.com/ucb-ist-drupal/training_pantheon/issues/3)

5. Add administrators on every site.

  The approach we take is to add every student as an administrator on
  every site.  That way the instructor can assign any site to any
  student and the student will have full permissions.  (Yes they could
  hack each other.  Perhaps that will be encouraged in the advanced
  classes...).

  We use the CAS module, so we add "cas users" and then assign the
  administrator role to them.

