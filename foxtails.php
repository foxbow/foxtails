<?php
error_reporting( E_ALL | E_STRICT );

$db_name="foxtails.sq3";

require_once( "foxtail_db.php" );

if( isset($_POST['available']) ) {
    $result="";
    $first=1;
    foreach( $_POST['available'] as $part ) {
		if( $first == 0 ) $result .= " ";
		else $first=0;
		$result .= $part;    	
    }
    setSetting( "available", "$result" );
}

/*
 * setting setlang in the address line will trigger language selection
 * So far this is just for debugging purposes
 */
if( isset( $_POST['lang'] ) ) setSetting( "lang", $_POST['lang'] );
else if( isset( $_GET['setlang'] ) ) selectLanguage();
$language=getSetting( "lang" );
if( $language == "" ) selectLanguage();

// $language=http_negotiate_language ( getTranslations() );
putenv("LANG=" . $language); // Win32
setlocale(LC_ALL, $language); // Linux

$domain = "messages";
bindtextdomain($domain, "./locale"); 
bind_textdomain_codeset($domain, 'UTF-8');
textdomain($domain);

if( isset( $_GET['cmd'] ) ) {
    if( $_GET['cmd'] == "qsetrate" ) {
        rateCocktail( $_GET['cid'], $_GET['rate'] );
        echo "rateCocktail( ".$_GET['cid'].", ".$_GET['rate']." );";
        return;
    }
}

/*
 * create a cookie compatible line from the array of all known parts.
 */
function allParts() {
	$result = "";
	$first=1;
	$parts=getParts();
	foreach( $parts as $part ) {
		if( $first == 0 ) $result .= " ";
		else $first=0;
		$result .= $part['id'];
	}
	return $result;
}

$vals=getSetting( "available" );
if( $vals == "" ) $vals=allParts();
$vals=explode( " ", $vals );
$available=array();
foreach( $vals as $part ) $available[$part]=$part;

global $admin, $styles;

/*
 * static definitions and helper arrays
 */
$amounts = array( '0.5', '1', '1.5', '2', '2.5', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15' );
$parttypes = array( gettext("Alkohol (+ 37,5%)"), gettext("Likör"), gettext("Nicht alkoholisch"), gettext("Sonstiges"), gettext("Deko") );

/*
 * This estimates the alcohol rate of the cocktail and 
 * returns the guessed category virgin/fruity/cocktail/strong
 */
function computeStyleId( $parts ) {
	$amount=0;
	$alc=0;
	foreach( $parts as $part ) {
		switch( $part['part'] ) {
		case 1: // Nichts
			break;
		case 2: // Eiswürfel
			$amount += $part['count'] * 0.25;
			break;
		case 3: // Crushed Ice
			$amount += $part['count'] * 0.5;
			break;
		case 4: // Rocks
			// $amount += $part['count'] * 0.25;
			break;
		default:
			if( $part['measure'] == 1 ){
				$amount += $part['count'];
				if( $part['type'] == 0 ) $alc += $part['count']*40;
				if( $part['type'] == 1 ) $alc += $part['count']*20;
			}
			break;
		}	
	}

	if( $alc == 0 ) return 1;
	if($amount < 2) return 5;
	$vol=round($alc/$amount);
	if( $vol < 10 ) return 2;
	if( $vol < 13 ) return 3;
	if( ( $vol < 15) && ( $alc < 200 ) ) return 3;
	return 4;
}

/*
 * estimates the volume of the cocktail. Handy for deciding 
 * what kind of glass is needed
 */
function printAmount( $parts ){
	$glass=0;   // Space used in glass
	$amount=0;  // Actual content
	$alc=0;     // Estimated strength
	foreach( $parts as $part ) {
		switch( $part['part'] ) {
		case 1: // Nichts
			break;
		case 2: // Eiswürfel
			$glass += $part['count'] * 0.5;  // Icecube counts as 0.005l
			$amount += $part['count'] * 0.5;
			break;
		case 3: // Crushed Ice
			$glass += $part['count'];
			$amount += $part['count'] * 0.5;  // Half of the ice is actually water
			break;
		case 4: // Rocks
			$glass += $part['count'];
			break;
		default:
			if( $part['measure'] == 1 ){
				$amount += $part['count'];
				$glass  += $part['count'];
				if( $part['type'] == 0 ) $alc += $part['count']*40;
				if( $part['type'] == 1 ) $alc += $part['count']*20;
			}
			break;
		}
	}

	// round up, so a 0.21l drink will ask for a 0.3l glass	
	$glass=round(($glass+4)/10)/10;
	
	return "( ".$glass."l / ".round($alc/$amount)."% )";
}

/*
 * print part by class, gives a first estimate of strength
 * and headache quotient by the colours.
 */
function printPart( $part ) {
	global $available;
	$desc= "<span style='";
	switch( $part['type'] ) {
	case 0:
		$desc.="color:#400000;";
		break;
	case 1:
		$desc.="color:#000000;";
		break;
	case 2:
		$desc.="color:#004000;";
		break;
	default:
		$desc.="color:#000040;";
		break;
	}

    if( isset( $part['part'] ) ) $partid=$part['part'];
    else $partid=$part['id'];
	if( !isset( $available[$partid] ) ) $desc.="background-color:#ffe0e0;";
	$desc .= "'>".$part['name']."</span>";
	return $desc;
}

/*
 * Create a human readable recipe for the given Cocktail id
 */
function getRecipe( $cockid ) {
	global $admin;
	$name   = getCocktailName( $cockid );
	$type	= getCocktailType( $cockid );
	$parts  = getCocktailParts( $cockid );
	$recipe = getCocktailRecipe( $cockid );

	if( $admin == 'on' ) {
		$desc =  "<a href='?cmd=edit&admin=on&id=$cockid'><h3>$name</h3></a>\n";
	} else {
		$desc =  "<h3>$name</h3>\n";
	}

	$desc .= "<b>$type</b> ".printAmount($parts)."<br>\n";
	$desc .= "<center><table border='0'>";
	foreach( $parts as $part ) {
		$measure  = getMeasure( $part['measure'] );
		$desc .= "<tr><td>&bullet;</td>";
		$desc .= "<td>".$part['count']."</td>";
		$desc .= "<td>$measure</td>";
		$desc .= "<td>".printPart( $part )."</td>";
		$desc .= "<td>".$part['comment']."</td></tr>\n";
	}
	$desc .= "</table></table>\n";
	$desc .= "<p>".str_replace( "\n", "<br/>", $recipe)."</p>\n";
	return $desc;
}

/*
 * create a human readable list of ingredients.
 * This does NOT show any parts of the 'decoration' type!
 * Used for the menu
 */
function getShortList( $cock ) {
	$name   = $cock['name'];
	$parts  = getCocktailParts( $cock['id'] );
    $rate   = $cock['rate'];
    if( $rate == "" ) $rate=0;

	$desc = "<p>".$cock['id']." - ";
	$desc .= "<a href='?cmd=show&id=".$cock['id']."'><b>$name</b></a> ";
	$desc .= "<span id='dr".$cock['id']."' onclick='toggle(".$cock['id'].")' title='$rate'>";
	$desc .= printAmount($parts);
	$desc .= "</span>";
	$desc .= "<span id='cr".$cock['id']."' rate='$rate' style='color:";
	switch( $rate ) {
	    case 1:
    	    $desc .= "#f00;'>";
	        $desc .= "&nbsp;&hearts;";
	    break;
	    case 2:
	        $desc .= "#000;'>";
	        $desc .= "&nbsp;&#9760;";
	    break;
	    default:
	        $desc .= "#000;'>";
	    break;
	}
	$desc .= "</span>";
	$desc .= "<br>\n";
	$desc .= "<i>( ";

	$first=1;
	foreach( $parts as $part ) {
		if( $part['type'] < 3 ) {
			if( $first == 0 ) $desc .= ", ";
			else $first=0;
			$desc .= printPart( $part );
		}
	}
	$desc .= " )</i></p>\n";
	return $desc;
}

function allthere( $id ) {
	global $available;
	$parts = getCocktailParts( $id );
	foreach( $parts as $part ) {
		if( !isset( $available[ $part['part'] ] ) ) return false;
	}
	return true;
}

/*
 * List the given array of Cocktail IDs
 */
function listCocktails( $cocktails, $cols=4 ) {
	global $admin;
	$col=0;
	echo "<center><table>\n";
	foreach( $cocktails as $cocktail ) {
		if( 0 == $col ) echo "<tr>";
		if( allthere( $cocktail['id'] ) ) 
			echo "<td id='item'>";
		else
			echo "<td id='noitem'>";
		echo "<a href='?cmd=show&id=".$cocktail['id'];
		if( $admin == 'on' ) echo "&admin=on";
		echo "'><b>".$cocktail['name']."</b></a>";
		if( isset( $cocktail['rank'] ) ) {
			echo " ";
			for( $j=0; $j<$cocktail['rank']; $j++ ) echo "*";
		}

		echo "<br>";
		if( isset( $cocktail['type'] ) && $cocktail['type'] != 1 ){
			$type = getTypeName( $cocktail['type'] );
			echo "<i>$type</i> ";
		}

		echo printAmount( getCocktailParts( $cocktail['id'] ) );

		echo "</td>";
		$col++;
		if( $cols == $col ) {
			echo "</tr>\n";
			$col=0;
		}
	} 
	if( $col > 0 ) {
		while ( $col++ < $cols ) echo "<td></td>";
		echo "</tr>\n";
	}
	echo "</table></center>\n";
}

$admin='off';
if( isset($_POST['admin']) ) $admin=$_POST['admin'];
if( isset($_GET['admin']) ) $admin=$_GET['admin'];
if( $admin=='on' ) {
	require_once( "edit.php" ); 
} else {
	require_once( "show.php" ); 
}
?>

