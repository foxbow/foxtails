<?PHP 
  if( isset($_POST['available']) ) {
    $result="";
    $first=1;
    foreach( $_POST['available'] as $part ) {
		if( $first == 0 ) $result .= " ";
		else $first=0;
		$result .= $part;    	
    }
    // One month
    setcookie( "foxtail", "$result", time()+30*60*24*30 );
    $available=$_POST['available'];
  }
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
