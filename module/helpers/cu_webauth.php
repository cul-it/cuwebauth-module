<?php

global $cu_webauth_secret_cache_name;
$cu_webauth_secret_cache_name = 'cu_webauth_net_id_secret';

function cu_webauth_verify_netid() {
  $msg[] = "cu_webauth_verify_netid";
  $msg[] = isset($_COOKIE['netid']) ? 'has netid' : 'no netid';
  $msg[] = isset($_COOKIE['verify_netid']) ? 'has verify_netid' : 'no verify_netid';
  drupal_set_message(implode(' ', $msg));
  $verified = FALSE;
  if (isset($_COOKIE['netid']) && isset($_COOKIE['verify_netid'])) {
    $secret = get_and_set_cu_webauth_secret();
    global $cu_webauth_secret_cache_name;
    if (md5($_COOKIE['netid'] . $secret) == $_COOKIE['verify_netid']) {
      $verified = TRUE;
    }
  }
  return $verified;
}

/**
 * Basic authentication method, redirects to a CUWebAuth protected directory,
 * and upon successful authentication, it will set a 'netid' cookie.
 */
function cu_webauth_authenticate($destination = '', $permit = '') {
  // $netID = getenv('REMOTE_USER');
  // if (isset($netID) && $netID != '') {
  //   return $netID;
  // }
  // else
  if (cu_webauth_verify_netid()) {
    return $_COOKIE['netid'];
  }
  else {
    //bring the user back to the path they started with, try to avoid the internal node number.
    //assumes use of 'friendly' URL's
    get_and_set_cu_webauth_secret();
    unset($_GET['destination']);
    $path = drupal_get_path('module', 'cu_webauth') . '/authenticate/';
    if (!empty($permit)) {
      $path .= "$permit/";
    }
    $path .= 'index.php';

    if (empty($destination)) {
      $destination = request_uri();
    }

    drupal_goto($path, array('query' => array('destination' => $destination)));
  }
}

/**
 * Simulate a CUWebAuth logout.
 */
function cu_webauth_do_logout() {
  unset($_COOKIE['netid']);
  unset($_COOKIE['verify_netid']);
  //setcookie('netid', '', REQUEST_TIME - 3600);
  //setcookie('verify_netid', '', REQUEST_TIME - 3600);
  setcookie('netid', "", time() - 3600, '/', '.cornell.edu');
  setcookie('verify_netid', "", time() - 3600, '/', '.cornell.edu');
}

/**
 * Call cuwebauth_logout() from client.
 */
function cu_webauth_logout_from_url() {
  $logout_url = NULL;
  if (isset($_GET['$logout_url'])) {
    $logout_url = $_GET['$logout_url'];
  }
  cu_webauth_logout($logout_url);
}


function cu_webauth_get_cuwebauth($node) {
  if (isset($node->nid)) {
    $nid = $node->nid;
    $result = db_query('SELECT nid FROM {cuwebauth} where nid = :nid',
    array(':nid' => $node->nid));
    return $result->fetchColumn();

    /*
    $result = db_select('cuwebauth', 'c')
      ->fields('c', array('nid'))
      ->condition('nid', $node->nid)
      ->execute();
    */
    }
  else {
    return false;
    }
}

function cu_webauth_manage_cuwebuath($node) {
  if (isset($node->cuwebauth)) {
    $cuwebauth = cu_webauth_get_cuwebauth($node);
    if ($node->cuwebauth && ! $cuwebauth) {
      // TODO Please review the conversion of this statement to the D7 database API syntax.
      /* db_query('INSERT INTO {cuwebauth} (nid) VALUES (%d)', $node->nid) */
      $id = db_insert('cuwebauth')
  ->fields(array(
    'nid' => $node->nid,
  ))
  ->execute();
    }
    else if (! $node->cuwebauth && $cuwebauth) {
      // TODO Please review the conversion of this statement to the D7 database API syntax.
      /* db_query('DELETE FROM {cuwebauth} WHERE nid = %d', $node->nid) */
      db_delete('cuwebauth')
  ->condition('nid', $node->nid)
  ->execute();
    }
  }
}


function _get_random_string($length = 10, $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz') {
  $string = '';
  for ($p = 0; $p < $length; $p++) {
    $string .= $characters[mt_rand(0, strlen($characters) -1)];
  }
  return $string;
}

function get_and_set_cu_webauth_secret($refresh = FALSE) {
  static $cu_webauth_secret;
  global $cu_webauth_secret_cache_name;
  if (($cached = cache_get($cu_webauth_secret_cache_name, 'cache')) && ! empty($cached->data) && ! $refresh) {
    $cu_webauth_secret = $cached->data;
  }
  else {
    $cu_webauth_secret = _get_random_string();
    cache_set($cu_webauth_secret_cache_name, $cu_webauth_secret, 'cache');
  }
  return $cu_webauth_secret;
}

