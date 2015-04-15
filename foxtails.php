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
    if( $_GET['cmd'] == "booklet" ) {
        header('Content-type: application/postscript');
        header('Content-Disposition: attachment; filename="cocktails.ps"');
       	$cocktails = getCocktailsNum();
        booklet( $cocktails );
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
$amounts = array( '0.5', '1', '1.5', '2', '2.5', '3', '4', '5', '6', '7', '8', 
        '9', '10', '11', '12', '13', '14', '15' );
$parttypes = array( gettext("Alkohol (+ 37,5%)"), gettext("Likör"), 
        gettext("Nicht alkoholisch"), gettext("Sonstiges"), gettext("Deko") );

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
			$amount += $part['count'] * 0.5;  // Half of the ice is air
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
	
	return "(&nbsp;".$glass."l&nbsp;/&nbsp;".round($alc/$amount)."%&nbsp;)";
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
		$desc =  "<h3><a href='?cmd=edit&admin=on&id=$cockid'>$name</a>";
		$desc .= printRate( getCocktail( $cockid ) )."</h3>\n";
	} else {
		$desc =  "<h3>$name".printRate( getCocktail( $cockid ) )."</h3>\n";
	}

	$desc .= "<b>$type</b> ".printAmount($parts)."<br>\n";
//	$desc .= "<center><table border='0'>";
	$desc .= "<table border='0'>";
	foreach( $parts as $part ) {
		$measure  = getMeasure( $part['measure'] );
		$desc .= "<tr><td>&bullet;</td>";
		$desc .= "<td>".$part['count']."</td>";
		$desc .= "<td>$measure</td>";
		$desc .= "<td>".printPart( $part )."</td>";
		$desc .= "<td>".$part['comment']."</td></tr>\n";
	}
//	$desc .= "</table></center>\n";
	$desc .= "</table>\n";
	$desc .= "<p>".str_replace( "\n", "<br/>", $recipe)."</p>\n";
	return $desc;
}

/**
 * prints the star rating of a cocktail
 * also adds the foundation needed for changing the rating
 */
function printRate( $cock ) {
    $rate   = $cock['rate'];
    if( $rate == "" ) $rate=0;

	$desc = "<span id='cr".$cock['id']."' title='$rate' ";
	$desc .= "onselectstart='return false' ";
	$desc .= "onclick='toggle(".$cock['id'].")' style='color:";
	switch( $rate ) {
	    case 1:
	        $desc .= "#300;'>&nbsp;";
	        $desc .= "&#9760;&#9734;&#9734;&#9734;&#9734;";
	    break;
	    case 2:
	        $desc .= "#600;'>&nbsp;";
	        $desc .= "&#9733;&#9733;&#9734;&#9734;&#9734;";
	    break;
	    case 3:
	        $desc .= "#900;'>&nbsp;";
	        $desc .= "&#9733;&#9733;&#9733;&#9734;&#9734;";
	    break;
	    case 4:
	        $desc .= "#b00;'>&nbsp;";
	        $desc .= "&#9733;&#9733;&#9733;&#9733;&#9734;";
	    break;
	    case 5:
	        $desc .= "#f00;'>&nbsp;";
	        $desc .= "&#9733;&#9733;&#9733;&#9733;&#9733;";
	    break;
	    default:
	        $desc .= "#000;'>&nbsp;";
	        $desc .= "&#9734;&#9734;&#9734;&#9734;&#9734;";
	    break;
	}
	$desc .= "</span>";
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

	$desc = "<p>".$cock['id']." - ";
	$desc .= "<a href='?cmd=show&id=".$cock['id']."'><b>$name</b></a> ";
	$desc .= printAmount($parts);
    $desc .= printRate($cock);
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
function listCocktails( $cocktails, $cols=5 ) {
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
		echo "<br>";
		echo printRate( $cocktail );
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

/**
 * make a line of text Postscript compatible, especially replacing
 * Umlaute
 * @todo: this may still create bogus stuff when brackets are involved
 */
function toPS( $text ) {
    $text = trim( $text );
    $text = str_replace( "&nbsp;", " ", $text );
    $text = str_replace( "ä", ") show /adieresis glyphshow (", $text );
    $text = str_replace( "ö", ") show /odieresis glyphshow (", $text );
    $text = str_replace( "ü", ") show /udieresis glyphshow (", $text );
    $text = str_replace( "Ä", ") show /Adieresis glyphshow (", $text );
    $text = str_replace( "Ö", ") show /Odieresis glyphshow (", $text );
    $text = str_replace( "Ü", ") show /Udieresis glyphshow (", $text );
    $text = str_replace( "ß", ") show /germandbls glyphshow (", $text );
    $retval="($text) show\n";
    return $retval;
}

/**
 * break a bunch of text up in multiple lines for output in Postscript
 */
function toMultiLine( $text, $offset ) {
    $retval = "";
    $buff="";
    $lines = explode( "\n", $text );
    foreach( $lines as $line ) {
        $words = explode( " ", $line );
        foreach( $words as $word ) {
            if( ( strlen($buff) + strlen( $word ) ) > 90 ) {
                $retval .= "20 $offset moveto\n";
                $retval .= toPS( $buff );
                $offset -= 13;
                $buff = "";
            }
            $buff .= $word." ";
        }
        if( trim($buff) != "" ) {
            $retval .= "20 $offset moveto\n";
            $retval .= toPS( $buff );
            $offset -= 13;
            $buff="";
        }            
    }
    return array( $retval, $offset );
}

/**
 * create a postscript representation of a cocktail
 */
function psRecipe( $cockid, $offset ) {
	$name   = getCocktailName( $cockid );
	$type	= getCocktailType( $cockid );
	$parts  = getCocktailParts( $cockid );
	$recipe = getCocktailRecipe( $cockid );

    $offset -= 16;
    $desc = "/Times-Roman findfont 16 scalefont setfont\n";
    $desc .= "20 $offset moveto\n";
    $desc .= toPS( "[$cockid] $name - $type ".printAmount($parts));
    $offset -= 20;
    $desc .= "/Helvetica findfont 12 scalefont setfont\n";
	foreach( $parts as $part ) {
	    $desc .= "20 $offset moveto\n";
		$desc .= toPS( $part['count']." ".getMeasure( $part['measure'] ) );
	    $desc .= "75 $offset moveto\n";
	    $buff = $part['name'];
		if( $part['comment'] != "" ) $buff .= " - ".$part['comment'];
		$desc .= toPS($buff);
		$offset -= 14;
	}
    $offset -= 10;
    $desc .= "/Times-Roman findfont 12 scalefont setfont\n";
    $res = toMultiLine( $recipe, $offset );
    $offset=$res[1];
	$desc .= $res[0];
    $offset -= 10;
	return array( $desc, $offset );
}

/**
 * create a postscript file for all cocktail recipes
 * takes care of basic layout too
 * @todo: some output prettification
 */
function booklet( $cocktails ) {
    echo "%!PS-Adobe-2.0

%%Creator: Foxtails
%%Title: Cocktails
%%DocumentData: Clean8Bit
%%Origin: 0 0
%%LanguageLevel: 2
%%DocumentMedia: a4 595 842 0 () ()

%%Page: 1 1\n";

    $page=1;
    $offset=822;
	if( !empty( $cocktails ) ) {
		foreach( $cocktails as $cocktail ) {
		    $info=psRecipe( $cocktail['id'], $offset );
		    if( $info[1] < 40 ) {
                echo "showpage\n";
                $page++;
                echo "%%Page: $page $page\n";
                $offset = 822;
			    $info=psRecipe( $cocktail['id'], $offset );
            }
            $offset=$info[1];
			echo $info[0];
		}
	}

    echo "showpage
%%EOF\n";
    return;
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

