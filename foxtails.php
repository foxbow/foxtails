<?php
$db_name="cocktails.sq3";

require_once( "cocktail_db.php" );
error_reporting( E_ALL | E_STRICT );

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

if( isset( $_POST['lang'] ) ) setSetting( "lang", $_POST['lang'] );
$language=getSetting( "lang" );
if( $language == "" ) selectLanguage();

// $language = "en";
putenv("LANG=" . $language); // Win32
setlocale(LC_ALL, $language); // Linux

// Set the text domain as "messages"
$domain = "messages";
// bindtextdomain($domain, "./locale/nocache"); 
bindtextdomain($domain, "./locale"); 
bind_textdomain_codeset($domain, 'UTF-8');
textdomain($domain);

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

$amounts = array( '0.5', '1', '1.5', '2', '2.5', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15' );
$parttypes = array( gettext("Alkohol (+ 37,5%)"), gettext("Likör"), gettext("Nicht alkoholisch"), gettext("Sonstiges"), gettext("Deko") );

function computeStyleId( $parts ) {
	$amount=0;
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
	$vol=round($alc/$amount);
	if( $vol < 10 ) return 2;
	if( $vol < 13 ) return 3;
	if( ( $vol < 15) && ( $alc < 200 ) ) return 3;
	return 4;
}

function printAmount( $parts ){
	$glass=0;
	$amount=0;
	$alc=0;
	foreach( $parts as $part ) {
		switch( $part['part'] ) {
		case 1: // Nichts
			break;
		case 2: // Eiswürfel
			$glass += $part['count'];
			$amount += $part['count'] * 0.25;
			break;
		case 3: // Crushed Ice
			$glass += $part['count'];
			$amount += $part['count'] * 0.5;
			break;
		case 4: // Rocks
			$glass += $part['count'];
			// $amount += $part['count'] * 0.25;
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
	
	$glass=round(($glass+5)/10)/10;
	
//	return "( ".$amount."cl / ".round($alc/$amount)."% )";
	return "( ".$glass."l / ".round($alc/$amount)."% )";
}

function printPart( $type, $name ) {
	$desc= "<span style='";
	switch( $type ) {
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
	$desc .= "'>$name</span>";
	return $desc;
}

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
		$desc .= "<td>".printPart( $part['type'], $part['name'] )."</td>";
		$desc .= "<td>".$part['comment']."</td></tr>\n";
	}
	$desc .= "</table></table>\n";
	$desc .= "<p>".str_replace( "\n", "<br/>", $recipe)."</p>\n";
	return $desc;
}

function getShortList( $cock ) {
	$name   = $cock['name'];
	$parts  = getCocktailParts( $cock['id'] );

	$desc = "<p>".$cock['id']." - ";
	$desc .= "<a href='?cmd=show&id=".$cock['id']."'><b>$name</b></a> ".printAmount($parts)."<br>\n";
	$desc .= "<i>( ";

	$first=1;
	foreach( $parts as $part ) {
		if( $part['type'] < 4 ) {
			if( $first == 0 ) $desc .= ", ";
			else $first=0;
			if( $part['type'] < 4 ) 
				$desc .= printPart( $part['type'], $part['name'] );
		}
	}
	$desc .= " )</i></p>\n";
	return $desc;
}

function listCocktails( $cocktails, $cols=4 ) {
	global $admin;
	$col=0;
	echo "<center><table>\n";
	foreach( $cocktails as $cocktail ) {
		if( 0 == $col ) echo "<tr>";
		echo "<td id='item'><a href='?cmd=show&id=".$cocktail['id'];
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

