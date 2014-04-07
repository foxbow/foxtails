<?php

// Set Default command - show cocktail current card
$cmd='showcard';
// Check if a command is set via HTTP GET (in a href)
if( isset( $_GET['cmd'] ) ) $cmd=$_GET['cmd'];
// Check if a command is set via HTTP POST (in a form)
if( isset( $_POST['cmd'] ) ) $cmd=$_POST['cmd']; 

if( $cmd == 'showcard' ) {
	// Show current cocktail menu
	echo "<h2><a href='?cmd=all'>".gettext("Cocktailkarte")."</a></h2>\n";
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

echo "<div id='linklist'>\n";
echo "<a href='?cmd=all'>".gettext("Alle Cocktails")."</a> - ";
echo "<a href='?cmd=search'>".gettext("Cocktails suchen")."</a> - ";
echo "<a href='?cmd=parts'>".gettext("Zutatenliste")."</a> - ";
echo "<a href='?cmd=showcard'>".gettext("Karte")."</a>";
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

if( $cmd == 'search' ) {
	if( isset( $_POST['id0'] ) ){
		$ids=array( $_POST['id0'], $_POST['id1'],$_POST['id2'],$_POST['id3'], );
		echo "<h2>".gettext("Cocktails mit")." ";
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
		echo "<h2>".gettext("Cocktails ohne")." ";
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
		echo "<a href='$link'>".gettext("Lange Liste")."</a>\n";
		echo "<hr>\n";
	}

	echo "<form action='' method='post'>\n";
    $parts = getParts();
    echo gettext("Suche nach Cocktails mit")."\n";
	
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
    echo gettext("Suche nach Cocktails ohne")."\n";
	
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
    echo "  <input type='submit' value='".gettext("Los")."'>\n";
	echo "</form>\n";
	echo "<hr>\n";

	echo "<form action='' method='post'>\n";
    $types = getTypes();
    echo "<label for='type'>".gettext("Zeige alle")." </label>\n";
    echo "<select id='type' name='type'>\n";
   	foreach( $types as $type ){
       	echo "  <option value='".$type['id']."'>".$type['name']."\n";
    }
    echo "</select>\n";
    echo "  <input type='hidden' name='cmd' value='search'>\n";
    echo "  <input type='submit' value='".gettext("Los")."'>\n";
	echo "</form>\n";
}

if( $cmd == 'parts' ) {
	echo "<h2>".gettext("Vorhandene Zutaten")."</h2>\n";

	$cols=4;
	$numc = 1.0/getCocktailNum();

	echo "<form action='' method='post'>\n";
	echo "<input type='submit' value='".gettext("Setzen und Karte zeigen")."'><br>\n";
	echo "<input type='hidden' name='cmd' value='showcard'>\n";
	
	echo "<center><table>\n";
	for( $type=0; $type<5; $type++ ){
		$col=0;
		echo "<tr><th colspan='$cols'>".$parttypes[ $type ]."</th></tr>\n";
		$parts = getPartsByType( $type );
		foreach( $parts as $part ) {
			if( $part['id'] != 1 ) {
				if( 0 == $col ) echo "<tr>";
				echo "<td style='padding:5px;background-color:#eee;'>";
				echo "<input type='checkbox' name='available[]' id='".$part['id']."' value='".$part['id']."'";
				if( isset( $available[ $part['id'] ] ) ) echo " checked";
				echo ">\n";
				echo "<label for='".$part['id']."'>".$part['name'];
				if( ($numc > 0 ) && ($part['num'] > 0 ) ){
					$x=$part['num']*$numc;
					$perc=round( ( -(($x-1.0)*($x-1)) + 1.0 ) * 15 );
					echo " <div style='display: inline-block; width: 15px; height: ".$perc."px; background-color: teal;'></div>\n";
				} else {
					echo " <div style='display: inline-block; color: red;'>X</div>";
				}
				if( $part['comment'] != "" ) echo "<br>".$part['comment'];
				echo "</label>";

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
	echo "<input type='submit' value='".gettext("Setzen und Karte zeigen")."'>\n";
	echo "</form>";
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
	echo "<a href='?admin=on'>".gettext("Bearbeiten")."</a>";
	echo "</div>\n";
?>

