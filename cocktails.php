<?php
$db_name="cocktails.sq3";

require_once( "cocktail_db.php" );
error_reporting( E_ALL | E_STRICT );

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

if( isset($_POST['available'] ) ) $vals=$_POST['available'];
else {
	if( isset($_COOKIE["foxtail"]) ) $vals=$_COOKIE["foxtail"];
	else $vals=allParts();
	$vals=explode( " ", $vals );
}
$available=array();
foreach( $vals as $part ) $available[$part]=$part;

global $admin, $styles;
$admin=false;

$amounts = array( '0.5', '1', '1.5', '2', '2.5', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15' );
$parttypes = array( "Alkohol (+ 37,5%)", "Likör", "Nicht alkoholisch", "Sonstiges" );

function linkList( $edit ) {
	echo "<div style='background-color:#eee;padding:5px;margin:5px;text-align:center;'>\n";
	if( $edit ) echo "<a href='?cmd=measures'>Massliste</a>";
// <a href='?cmd=types'>Typliste</a>";
	else echo "<a href='?cmd=all'>Alle Cocktails</a> -
<a href='?cmd=search'>Cocktails suchen</a> - 
<a href='?cmd=new'>Neuen Cocktail eingeben</a> - 
<a href='?cmd=parts'>Zutatenliste</a> -
<a href='?cmd=card'>Karte</a>";
	echo "</div>\n";
}

function computeStyleId( $parts ) {
	$amount=0;
	$alc=0;
	foreach( $parts as $part ) {
		if( $part['measure'] == 1 ){
			$amount += $part['count'];
			if( $part['type'] == 0 ) $alc += $part['count']*40;
			if( $part['type'] == 1 ) $alc += $part['count']*20;
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
	$amount=0;
	$alc=0;
	foreach( $parts as $part ) {
		if( $part['measure'] == 1 ){
			$amount += $part['count'];
			if( $part['type'] == 0 ) $alc += $part['count']*40;
			if( $part['type'] == 1 ) $alc += $part['count']*20;
		}	
	}
	return "( ".$amount."cl / ".round($alc/$amount)."% )";
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
	$name   = getCocktailName( $cockid );
	$type	= getCocktailType( $cockid );
	$parts  = getCocktailParts( $cockid );
	$recipe = getCocktailRecipe( $cockid );

	$desc =  "<a href='?cmd=edit&id=$cockid'><h3>$name</h3></a>\n";

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

//	setCocktailType( $cock['id'], computeStyleId( $parts ) );

	$first=1;
	foreach( $parts as $part ) {
		if( $part['type'] < 4 ) {
			if( $first == 0 ) $desc .= ", ";
			else $first=0;
			$desc .= printPart( $part['type'], $part['name'] );
		}
	}
	$desc .= " )</i></p>\n";
	return $desc;
}

function listCocktails( $cocktails, $cols=4 ) {
	$col=0;
	echo "<center><table>\n";
	foreach( $cocktails as $cocktail ) {
		if( 0 == $col ) echo "<tr>";
		echo "<td style='padding:5px;background-color:#eee;'><a href='?cmd=show&id=".$cocktail['id']."'><b>".$cocktail['name']."</b></a>";
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

$cmd='parts';
if( isset( $_GET['cmd'] ) ) $cmd=$_GET['cmd'];
if( isset( $_POST['cmd'] ) ) $cmd=$_POST['cmd']; 

if( $cmd == 'card' ) {
	echo "<h2><a href='?cmd=all'>Cocktailkarte</a></h2>\n";
	$types = getTypes();
	foreach( $types as $type ) {
		$cocktails = findCocktailsByTypeAndParts( $type['id'], $available );
		if( !empty( $cocktails ) ) {
			echo "<hr>\n<h3>".$type['name']."</h3>\n";
			foreach( $cocktails as $cocktail ) {
				echo getShortList( $cocktail );
			}
		}
	}
	echo "\n";
	return;
}

linkList( $admin );
echo "<div style='margin-left:3%;margin-right:3%;text-align:center;'>\n";

if( $cmd == 'longlist' ) {
	$ids=explode( ",", $_GET['ids'] );
	foreach( $ids as $id ) {
		if( $id != "" ) {
			echo getRecipe( $id );
			echo "<hr>\n";
		}
	}
}

if( $cmd == 'search' ) {
	if( isset( $_POST['id0'] ) ){
		$ids=array( $_POST['id0'], $_POST['id1'],$_POST['id2'],$_POST['id3'], );
		echo "<h2>Cocktails mit ";
		for( $i=0; $i<4; $i++ ) {
			if( $ids[$i] != 1 ) {
				$name=getPartName( $ids[$i] );
				echo "$name ";
			}
		}
		echo "</h2>\n";

		$cocktails=findCocktailsByParts( $ids );
	}
	
	if( isset( $_POST['type'] ) ){
		$typeid=$_POST['type'];
		$type=getTypeName($typeid);
		echo "<h2>$type</h2>\n";
		$cocktails=findCocktailsByType( $typeid );
	}
	
	if( isset( $_POST['noid0'] ) ) {
		$ids=array( $_POST['noid0'], $_POST['noid1'],$_POST['noid2'],$_POST['noid3'], );
		echo "<h2>Cocktails ohne ";
		for( $i=0; $i<4; $i++ ) {
			if( $ids[$i] != 1 ) {
				$name=getPartName( $ids[$i] );
				echo "$name ";
			}
		}
		echo "</h2>\n";
		$cocktails=findCocktailsWithout( $ids );
	}

	if( isset( $cocktails ) ){
		listCocktails( $cocktails );
		$link="?cmd=longlist&ids=";
		foreach( $cocktails as $cockid ) {
			$link=$link.$cockid['id'].",";
		}
		echo "<a href='$link'>Lange Liste</a>\n";
		echo "<hr>\n";
	}

	echo "<form action='' method='post'>\n";
    $parts = getParts();
    echo "Suche nach Cocktails mit\n";
	
	for( $i=0; $i<4; $i++ ){
	    echo "<select name='id$i'>\n";
   		foreach( $parts as $part ){
			if( 1 == $part['id'] )
	   	    	echo "  <option selected value='".$part['id']."'>".$part['name']."\n";	
			else
	   	    	echo "  <option value='".$part['id']."'>".$part['name']."\n";
    	}
    	echo "</select>\n";
	}

    echo "  <input type='hidden' name='cmd' value='search'>\n";
    echo "  <input type='submit' value='Los'>\n";
	echo "</form>\n";
	echo "<hr>\n";

	echo "<form action='' method='post'>\n";
    $parts = getParts();
    echo "Suche nach Cocktails ohne\n";
	
	for( $i=0; $i<4; $i++ ){
	    echo "<select name='noid$i'>\n";
   		foreach( $parts as $part ){
			if( 1 == $part['id'] )
	   	    	echo "  <option selected value='".$part['id']."'>".$part['name']."\n";	
			else
	   	    	echo "  <option value='".$part['id']."'>".$part['name']."\n";
    	}
    	echo "</select>\n";
	}

    echo "  <input type='hidden' name='cmd' value='search'>\n";
    echo "  <input type='submit' value='Los'>\n";
	echo "</form>\n";
	echo "<hr>\n";

	echo "<form action='' method='post'>\n";
    $types = getTypes();
    echo "<label for='type'>Zeige alle </label>\n";
    echo "<select id='type' name='type'>\n";
   	foreach( $types as $type ){
       	echo "  <option value='".$type['id']."'>".$type['name']."\n";
    }
    echo "</select>\n";
    echo "  <input type='hidden' name='cmd' value='search'>\n";
    echo "  <input type='submit' value='Los'>\n";
	echo "</form>\n";
}

if( $cmd == 'addMeasure' ) {
	$name = $_POST['name'];
	newMeasure( $name );
	$cmd='measures';
}

if( $cmd == 'measures' ) {
	$admin=true;
	echo "<h2>Massliste</h2>\n";

	$parts = getMeasures();
	foreach( $parts as $part ) {
		echo $part['name']."<br>";
	}
	echo "<h2>Neues Mass</h2>\n";
	echo "<form action='' method='post'>\n";
	echo "  Name: <input name='name' type='text'><br>\n";
	echo "  <input type='hidden' name='cmd' value='addMeasure'>\n";
	echo "  <input type='submit' value='okay'>\n";
	echo "</form>\n";
}

if( $cmd == 'addType' ) {
	$name = $_POST['name'];
	newType( $name );
	$cmd='types';
}

if( $cmd == 'types' ) {
	$admin=true;

	echo "<h2>Typliste</h2>\n";

	$parts = getTypes();
	foreach( $parts as $part ) {
		echo $part['name']."<br>";
	}

	echo "<h2>Neuer Typ</h2>\n";
	echo "<form action='' method='post'>\n";
	echo "  Name: <input name='name' type='text'><br>\n";
	echo "  <input type='hidden' name='cmd' value='addType'>\n";
	echo "  <input type='submit' value='okay'>\n";
	echo "</form>\n";
}

if( $cmd == 'edpart' ) {
	$admin=true;

	$partid=$_GET['part'];
	$part = getPart( $partid );
	echo "<h2>".printPart( $part['type'], $part['name'] )."</h2>\n";
    echo "<form action='' method='post'>\n";
    echo "  Name: <input name='name' type='text' value='".$part['name']."'><br>\n";
    echo "	Kommentar: <input name='comment' type='text' value='".$part['comment']."'><br>\n";
    echo "  <input type='hidden' name='id' value='$partid'>\n";
    echo "  <input type='hidden' name='cmd' value='setPart'>\n";
    echo "  <select name='type'>\n";
	for( $type=0; $type<4; $type++ ) {
		if( $part['type'] == $type )
	   	    echo "  <option selected value='$type'>".$parttypes[ $type ]."\n";	
		else
	   	    echo "  <option value='$type'>".$parttypes[ $type ]."\n";	
 	}
   	echo "</select>\n";
    echo "  <input type='submit' value='okay'>\n";
    echo "</form>\n";

}

if( $cmd == 'setPart' ) {
	$name = $_POST['name'];
	$comment = $_POST['comment'];
	$type = $_POST['type'];
	if( !isset( $_POST['id'] ) )
		newPart( $name, $comment, $type );
	else
		setPart( $_POST['id'], $name, $comment, $type );

	$cmd='parts';
}


if( $cmd == 'parts' ) {
	$admin=true;

	echo "<h2>Zutatenliste</h2>\n";

	$cols=4;
	
	echo "<form action='' method='post'>\n";
	echo "<input type='hidden' name='cmd' value='card'>\n";
	
	echo "<center><table>\n";
	for( $type=0; $type<4; $type++ ){
		$col=0;
		echo "<tr><th colspan='$cols'>".$parttypes[ $type ]."</th></tr>\n";
		$parts = getPartsByType( $type );
		foreach( $parts as $part ) {
			if( $part['id'] != 1 ) {
				if( 0 == $col ) echo "<tr>";
				echo "<td style='padding:5px;background-color:#eee;'>";
				echo "<input type='checkbox' name='available[]' value='".$part['id']."'";
				if( isset( $available[ $part['id'] ] ) ) echo " checked";
				echo ">\n";
				echo "<a href='?cmd=edpart&part=".$part['id']."'>".$part['name']."</a><br>";
				echo $part['comment'];
				echo "</td>";

				$col++;
				if( $cols == $col ) {
					echo "</tr>\n";
					$col=0;
				}
			}
		} 
		if( $col > 0 ) {
			while ( $col++ < $cols ) echo "<td></td>";
			echo "</tr>\n";
		}
	}
	echo "</table></center>\n";
	echo "<input type='submit' value='Karte'>\n";
	echo "</form>";
	
	echo "<h2>Neue Zutat</h2>\n";
	echo "<form action='' method='post'>\n";
	echo "  Name: <input name='name' type='text'><br>\n";
	echo "	Kommentar: <input name='comment' type='text'><br>\n";
	echo "  <input type='hidden' name='cmd' value='setPart'>\n";
	echo "  <select name='type'>\n";
	for( $type=0; $type<4; $type++ ) {
		if( 0 == $type )
	   	    echo "  <option selected value='$type'>".$parttypes[ $type ]."\n";	
		else
	   	    echo "  <option value='$type'>".$parttypes[ $type ]."\n";	
 	}
   	echo "</select>\n";
	echo "  <input type='submit' value='okay'>\n";
	echo "</form>\n";
}

if( $cmd == 'add' ) {
	$admin=true;

	$name   = $_POST['name'];
	$recipe = $_POST['recipe'];
//	$type	= $_POST['type'];

	$id = getCocktailID( $name );
	if ( $id == 0 ) {
		$id = addCocktail( $name, $recipe );

		for( $i=0; $i<10; $i++ ) {
			if( 1 != $_POST["part$i"] ) {
				addPart( $id, $_POST["count$i"], $_POST["measure$i"], $_POST["part$i"] );
			}
		}

		$parts  = getCocktailParts( $id );
		setCocktailType( $id, computeStyleId( $parts ) );

	} else {
		echo "<h2>Den Cocktail <a href='?cmd=show&id=$id'>$name'</a> gibt es schon!</h2>\n";
	}
	
	$cmd = 'all';
}

if( $cmd == 'change' ) {
	$admin=true;

	$id		= $_POST['id'];
	$name   = $_POST['name'];
	$recipe = $_POST['recipe'];
	$otype  = getCocktailType( $id );
	$type	= $_POST['type'];

	setCocktail( $id, $name, $recipe );
	removeParts( $id );

	for( $i=0; $i<10; $i++ ) {
		if( 1 != $_POST["part$i"] ) {
			addPart( $id, $_POST["count$i"], $_POST["measure$i"], $_POST["part$i"] );
		}
	}

	$parts  = getCocktailParts( $id );
	if( $type == $otype ) {
		$type=computeStyleId( $parts );
	}
	if( $type != $otype ) {
		setCocktailType( $id, $type );		
	}

	$cmd = 'show';
}

if( $cmd == 'edit' ) {
	$admin=true;

	if( !isset( $_GET['id'] ) ) {
		echo "<h2>Kein Cocktail angegeben!</h2>\n";
		$cmd='new';
	} else {
		$cockid=$_GET['id'];
		$name=getCocktailName( $cockid );
		echo "<h2>$name</h2>";
		echo "<form action='' method='post'>\n";
		echo "	<input type='hidden' name='id' value='$cockid'>\n";
		echo "  <label for='name'>Name:</label>\n";
		echo "  <input id='name' name='name' type='text' size='30' value='$name'>\n";
		$measures = getMeasures();
		$parts = getParts();
		$types = getTypes();
		$ctype = getCocktailTypeID( $cockid );
		echo "  <label for'type'>Ist ein</label>\n";
	   	echo "  <select id='type' name='type'>\n";
		foreach( $types as $type ){
			if( $ctype == $type['id'] ) { 
		 		echo "  <option selected value='".$type['id']."'>".$type['name']."\n";
			} else {
		   		echo "  <option value='".$type['id']."'>".$type['name']."\n";
		   	}
		}
	   	echo "</select><br>\n"; 

		$recipe=getCocktailParts( $cockid );

		for( $i=0; $i<10; $i++ ) {
			echo "<select name='count$i'>\n";
	   		foreach( $amounts as $amount ){
				if( isset( $recipe[$i] ) ) $check=$recipe[$i]['count'];
				else $check=1;
	   			if( $check == $amount ) { 
					echo "  <option selected>$amount\n";
	   			} else {
					echo "  <option>$amount\n";
				}
		    }
			echo "</select>\n"; 

			echo "<select name='measure$i'>\n";
	   		foreach( $measures as $measure ){
				if( isset( $recipe[$i] ) ) $check=$recipe[$i]['measure'];
				else $check=1;
	   			if( $check == $measure['id'] ) { 
					echo "  <option selected value='".$measure['id']."'>".$measure['name']."\n";
	   			} else {
					echo "  <option value='".$measure['id']."'>".$measure['name']."\n";
				}
		    }
			echo "</select>\n"; 
			echo "<select name='part$i'>\n";
	   		foreach( $parts as $part ){
				if( isset( $recipe[$i] ) ) $check=$recipe[$i]['part'];
				else $check=1;
				
	   			if( $check == $part['id'] ) { 
		    		echo "  <option selected value='".$part['id']."'>".$part['name']."\n";
	   			} else {
		    		echo "  <option value='".$part['id']."'>".$part['name']."\n";
		    	}
		    }
			echo "</select><br>\n"; 
		}
		echo "  <label for='kommentar'>Zubereitung:</label><br>\n";
		echo "  <textarea id='kommentar' name='recipe' cols='80' rows='10'>";
		echo getCocktailRecipe( $cockid );
		echo "</textarea><br>\n";
		echo "  <input type='hidden' name='cmd' value='change'>\n";
		echo "  <input type='submit' value='okay'>\n";
		echo "</form>\n";
	}
}

if( $cmd == 'new' ) {
	$admin=true;

	echo "<h2>Neuen Cocktail eingeben</h2>\n";

	echo "<form action='' method='post'>\n";
    echo "  <label for='name'>Name:</label>\n";
    echo "  <input id='name' name='name' type='text' size='30'>\n";
    $measures = getMeasures();
    $parts = getParts();
/*	$types = getTypes();
	echo "  <label for'type'>Ist ein</label>\n";
   	echo "  <select id='type' name='type'>\n";
	foreach( $types as $type ){
		if( 1 == $type['id'] ) { 
     		echo "  <option selected value='".$type['id']."'>".$type['name']."\n";
		} else {
       		echo "  <option value='".$type['id']."'>".$type['name']."\n";
       	}
    }
   	echo "</select>";
*/
	echo "<br>\n"; 
    for( $i=0; $i<10; $i++ ) {
    	echo "<select name='count$i'>\n";    	
		foreach( $amounts as $amount ){
			if( 1 == $amount ) { 
				echo "  <option selected>$amount\n";
			} else {
				echo "  <option>$amount\n";
			}
		}
    	echo "</select>\n"; 

    	echo "<select name='measure$i'>\n";
   		foreach( $measures as $measure ){
   			if( 1 == $measure['id'] ) { 
        		echo "  <option selected value='".$measure['id']."'>".$measure['name']."\n";
   			} else {
        		echo "  <option value='".$measure['id']."'>".$measure['name']."\n";
        	}
        }
    	echo "</select>\n"; 
    	echo "<select name='part$i'>\n";
   		foreach( $parts as $part ){
   			if( 1 == $part['id'] ) { 
        		echo "  <option selected value='".$part['id']."'>".$part['name']."\n";
   			} else {
        		echo "  <option value='".$part['id']."'>".$part['name']."\n";
        	}
        }
    	echo "</select><br>\n"; 
    }
	echo "  <label for='kommentar'>Zubereitung:</label><br>\n";
	echo "  <textarea id='kommentar' name='recipe' cols='80' rows='10'>";
	echo "Alle Zutaten mit Eis im Shaker mixen und in ein Glas auf Eis abseihen.";
	echo "</textarea><br>\n";
    echo "  <input type='hidden' name='cmd' value='add'>\n";
    echo "  <input type='submit' value='okay'>\n";
	echo "</form>\n";
}

if( $cmd == 'all' ) {
	echo "<h2>Alle Cocktails</h2>\n";
	$cocktails = getCocktails();
	listCocktails( $cocktails );
}

if( $cmd == 'show' ) {
	$cockid=$_GET['id'];
	echo getRecipe( $cockid );
}

echo "</div>\n";
linkList( $admin );
?>

