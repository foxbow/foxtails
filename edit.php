<?php
$cmd='all';
if( isset( $_GET['cmd'] ) ) $cmd=$_GET['cmd'];
if( isset( $_POST['cmd'] ) ) $cmd=$_POST['cmd']; 

if( !isset( $password ) || $password!=md5("foxtail") ) {
	echo "<h2>".gettext("Unlock")."</h2>\n";
	echo "<form action='' method='post'>\n";
	echo gettext("Passwort");
	echo ": <input name='password' type='password'><br>\n";
	echo "  <input type='hidden' name='admin' value='on'>\n";
	echo "  <input type='hidden' name='cmd' value='$cmd'>\n";
	echo "  <input type='submit' value='okay'>\n";
	echo "</form>\n";
	
	echo "<div id='linklist'>\n";
	echo "<a href='?admin=off'>".gettext("Anzeigen")."</a>";
	echo "</div>\n";
	return;
}

echo "<div id='linklist'>\n";
echo "<a href='?admin=on&cmd=all'>".gettext("Alle Cocktails")."</a> - ";
echo "<a href='?admin=on&cmd=new'>".gettext("Neuen Cocktail eingeben")."</a> - ";
echo "<a href='?admin=on&cmd=measures'>".gettext("Massliste")."</a> - ";
echo "<a href='?admin=on&cmd=parts'>".gettext("Zutatenliste")."</a> - ";
echo "<a href='?admin=on&cmd=types'>".gettext("Typliste")."</a>";
echo "</div>\n";

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


if( $cmd == 'addMeasure' ) {
	$name = $_POST['name'];
	newMeasure( $name );
	$cmd='measures';
}

if( $cmd == 'measures' ) {
	echo "<h2>".gettext("Massliste")."</h2>\n";

	$parts = getMeasures();
	foreach( $parts as $part ) {
		echo $part['name']."<br>";
	}
	echo "<h2>".gettext("Neues Mass")."</h2>\n";
	echo "<form action='' method='post'>\n";
	echo "  Name: <input name='name' type='text'><br>\n";
	echo "  <input type='hidden' name='admin' value='on'>\n";
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
	echo "<h2>".gettext("Typliste")."</h2>\n";

	$parts = getTypes();
	foreach( $parts as $part ) {
		echo $part['name']."<br>";
	}

	echo "<h2>".gettext("Neuer Typ")."</h2>\n";
	echo "<form action='' method='post'>\n";
	echo "  Name: <input name='name' type='text'><br>\n";
	echo "  <input type='hidden' name='admin' value='on'>\n";
	echo "  <input type='hidden' name='cmd' value='addType'>\n";
	echo "  <input type='submit' value='okay'>\n";
	echo "</form>\n";
}

if( $cmd == 'edpart' ) {
	$partid=$_GET['part'];
	$part = getPart( $partid );
	echo "<h2>".printPart( $part['type'], $part['name'] )."</h2>\n";
    echo "<form action='' method='post'>\n";
    echo "  Name: <input name='name' type='text' value='".$part['name']."'><br>\n";
    echo "	Kommentar: <input name='comment' type='text' value='".$part['comment']."'><br>\n";
    echo "  <input type='hidden' name='id' value='$partid'>\n";
	echo "  <input type='hidden' name='admin' value='on'>\n";
    echo "  <input type='hidden' name='cmd' value='setPart'>\n";
    echo "  <select name='type'>\n";
	for( $type=0; $type<5; $type++ ) {
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
	echo "<h2>".gettext("Zutatenliste")."</h2>\n";

	$cols=4;
	$numc = 1.0/getCocktailNum();
	echo "<center><table>\n";
	for( $type=0; $type<5; $type++ ){
		$col=0;
		echo "<tr><th colspan='$cols'>".$parttypes[ $type ]."</th></tr>\n";
		$parts = getPartsByType( $type );
		foreach( $parts as $part ) {
			if( $part['id'] != 1 ) {
				if( 0 == $col ) echo "<tr>";
				echo "<td style='padding:5px;background-color:#eee;'>";
				if( ($numc > 0 ) && ($part['num'] > 0 ) ){
					$x=$part['num']*$numc;
					$perc=round( ( -(($x-1.0)*($x-1)) + 1.0 ) * 15 );
					echo " <div style='display: inline-block; width: 15px; height: ".$perc."px; background-color: teal;'></div>\n";
				}

				echo "<a href='?cmd=edpart&admin=on&part=".$part['id']."'>".$part['name']."</a>";
				echo "<br>\n";
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
	
	echo "<h2>".gettext("Neue Zutat")."</h2>\n";
	echo "<form action='' method='post'>\n";
	echo "  Name: <input name='name' type='text'><br>\n";
	echo "	Kommentar: <input name='comment' type='text'><br>\n";
	echo "  <input type='hidden' name='cmd' value='setPart'>\n";
	echo "  <input type='hidden' name='admin' value='on'>\n";
	echo "  <select name='type'>\n";
	for( $type=0; $type<5; $type++ ) {
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
		echo "<h2>".gettext("Den Cocktail")." <a href='?cmd=show&id=$id'>$name'</a> ".gettext("gibt es schon")."!</h2>\n";
	}
	
	$cmd = 'all';
}

if( $cmd == 'change' ) {
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

if( $cmd == 'remove' ) {
	$id		= $_POST['id'];

	removeCocktail( $id );
	removeParts( $id );

	$cmd = 'all';
}

if( $cmd == 'edit' ) {
	if( !isset( $_GET['id'] ) ) {
		echo "<h2>".gettext("Kein Cocktail angegeben!")."</h2>\n";
		$cmd='new';
	} else {
		$cockid=$_GET['id'];
		$name=getCocktailName( $cockid );
		echo "<h2>$name</h2>";
		echo "<form action='' method='post'>\n";
		echo "	<input type='hidden' name='id' value='$cockid'>\n";
		echo "  <input type='hidden' name='admin' value='on'>\n";
		echo "	<input type='hidden' name='cmd' value='remove'>\n";
		echo "  <input type='submit' value=' * LÖSCHEN * '>\n";
		echo "</form><br>\n";
		echo "<form action='' method='post'>\n";
		echo "	<input type='hidden' name='id' value='$cockid'>\n";
		echo "  <label for='name'>Name:</label>\n";
		echo "  <input id='name' name='name' type='text' size='30' value='$name'>\n";
		$measures = getMeasures();
		$parts = getParts();
		$types = getTypes();
		$ctype = getCocktailTypeID( $cockid );
		echo "  <label for'type'>".gettext("Ist ein")."</label>\n";
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
		echo "  <label for='kommentar'>".gettext("Zubereitung").":</label><br>\n";
		echo "  <textarea id='kommentar' name='recipe' cols='80' rows='10'>";
		echo getCocktailRecipe( $cockid );
		echo "</textarea><br>\n";
		echo "  <input type='hidden' name='cmd' value='change'>\n";
		echo "  <input type='hidden' name='admin' value='on'>\n";
		echo "  <input type='submit' value='okay'>\n";
		echo "</form>\n";
	}
}

if( $cmd == 'new' ) {
	echo "<h2>".gettext("Neuen Cocktail eingeben")."</h2>\n";

	echo "<form action='' method='post'>\n";
    echo "  <label for='name'>Name:</label>\n";
    echo "  <input id='name' name='name' type='text' size='30'>\n";
    $measures = getMeasures();
    $parts = getParts();
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
	echo "  <label for='kommentar'>".gettext("Zubereitung").":</label><br>\n";
	echo "  <textarea id='kommentar' name='recipe' cols='80' rows='10'>";
	echo gettext("Alle Zutaten mit Eis im Shaker mixen und in ein Glas auf Eis abseihen.");
	echo "</textarea><br>\n";
	echo "  <input type='hidden' name='admin' value='on'>\n";
    echo "  <input type='hidden' name='cmd' value='add'>\n";
    echo "  <input type='submit' value='okay'>\n";
	echo "</form>\n";
}

if( $cmd == 'all' ) {
	echo "<h2>".gettext("Alle Cocktails")."</h2>\n";
	$cocktails = getCocktails();
	listCocktails( $cocktails );
}

if( $cmd == 'show' ) {
	$cockid=$_GET['id'];
	echo getRecipe( $cockid );
}

echo "</div>\n";
echo "<div id='linklist'>\n";
echo "<a href='?admin=off'>".gettext("Anzeigen")."</a> - ";
echo "<a href='?admin=logoff'>".gettext("Abmelden")."</a>";
echo "</div>\n";
?>

