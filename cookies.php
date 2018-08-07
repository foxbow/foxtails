<?PHP
  if( isset($_GET['admin']) && ($_GET['admin']=='logoff') ) {
    setcookie( "foxtail_pass", "" );
  } else {
    if( isset($_COOKIE["foxtail_pass"]) ) {
	  $password=$_COOKIE["foxtail_pass"];
    }
    if( isset($_POST['password']) ) {
      $password=md5($_POST['password']);
      setcookie( "foxtail_pass", $password );
    }
  }
?>
