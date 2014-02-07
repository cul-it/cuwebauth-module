<?php
// cu_webauth.admin.inc - page handlers for admin users


/**
 * callback function for cu_webauth/cuwebauth
 */
function cu_webauth_cuwebauth() {
  if (!cu_webauth_https()) {
    return MENU_ACCESS_DENIED;
  }
  if (cu_webauth_test_authentication()) {
    // if the user is already authenticated cu_webauth_authenticate_user
    // will return - just skip bogus error message
    drupal_goto("secure/cu_webauth/account"); // no leading slash
  }
  $path = "/secure/cu_webauth/account"; // destination requires leading slash
  $netid = cu_webauth_authenticate_user($path);
  // note: if this actually returns there is a problem
  $output = t('Unable to authenticate with CUWebAuth.');
  return ($output);
}

/**
 * callback function for cu_webauth/account
 */
function cu_webauth_account() {
  if (!cu_webauth_https()) {
    return MENU_ACCESS_DENIED;
  }
  // user should be cuwebautherized by now
  if (!cu_webauth_test_authentication()) {
    $output = t('Please log in via cu_webauth!');
  }
  else {
    $netid = cu_webauth_authenticate_user();
    $path = "secure/cu_webauth/hello"; // where to go after successful login
    if (cu_webauth_valid_user($netid)) {
      // this is a Drupal user - is it one cu_webauth created?
      if (cu_webauth_created_user($netid)) {
        // this is a user cu_webauth created
        cu_webauth_create_and_login_user($netid);
        drupal_goto($path);
      }
      else {
        $output = t('A user with this netID already exists. You may not create another account with this user name.');
      }
    }
    else {
      // no user with this name - create a new one
      $email = cu_webauth_get_email_address($netid);
      if ($email === FALSE) {
        // oops - no email, sorry pal
        $output = t('Unable to locate an email address for @netid.',
          array('@netid' => $netid));
      }
      else {
        drupal_set_message(t('Creating a new account for CUL user @netid.', array('@netid' => $netid)));
        cu_webauth_create_and_login_user($netid);
        drupal_goto($path);
      }
    }
  }
  return $output;
}

/**
 * callback function for cu_webauth/hello
 */
function cu_webauth_hello() {
  if (!cu_webauth_https()) {
    return MENU_ACCESS_DENIED;
  }
  // user should be cuwebautherized & logged in by now
  if (!cu_webauth_test_authentication() || !user_is_logged_in()) {
    $output = t('Please log in via cu_webauth!');
  }
  else {
    // show some user info
    global $user;
    $username = $user->name;
    $output = t('You are logged in as @username.<ul><li>!user</li><li>!logout</li></ul>',
      array(
        '@username' => $username,
        '!user' => l('Look at your user information', 'user'),
        '!logout' => l('Log out', 'user/logout'),
        )
      );
  }

  return $output;
}
