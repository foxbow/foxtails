<?PHP
if( !isset( $db_name ) ) trigger_error("db_name not set!",E_USER_ERROR);

/**
 * open the database.
 * If it does not exist, try to create it or at least prompt the
 * user/admin to do that.
 **/
function db_open(){
    global $db_name, $cid;
	if( ! isset( $cid ) ) {
		if( file_exists( $db_name ) ) {
		    $cid = new PDO( "sqlite:".$db_name );
		} else {
		    $cid = new PDO( "sqlite:".$db_name );
		    db_init();
		}
	}
	
    if($cid === false) trigger_error("Datenbankfehler", E_USER_ERROR);

    return $cid;
}

/**
 * SQL wrapper to do error checking and transactions automatically
 * Will always return the result set.
 **/
function db_exec( $SQL, $param=array() ){
	$cid=db_open();
	$cid->beginTransaction();
    $res = $cid->prepare( $SQL );
	if( ( false === $res ) || ( false === $res->execute( $param ) ) ) {
    	echo( $SQL."<br>\n");
      	print_r( $cid->errorInfo() );
		$cid->rollback();
	 } else {
    	$cid->commit();
    }
	return $res->fetchAll();
}

/**
 * Initialize the basics
 **/
function db_init(){
    $cid = db_open();
	$cid->beginTransaction();
	$cid->exec( "CREATE TABLE cocktail ( id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR( 32 ) UNIQUE, recipe VARCHAR( 1024 ), type INT, ice INT );" );
	$cid->exec( "CREATE TABLE recipe ( cockid INTEGER, measure INTEGER, count INTEGER, part INTEGER );" );
	$cid->exec( "CREATE TABLE measure ( id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR( 32 ) UNIQUE );" );
	$cid->exec( "CREATE TABLE part ( id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR( 32 ) UNIQUE, comment VARCHAR( 128 ), type INTEGER );" );
	$cid->exec( "CREATE TABLE type ( id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR( 32 ) UNIQUE );" );
    $cid->commit();
    newMeasure( 'cl' );
	newMeasure( 'Spritzer' );
	newMeasure( 'Rest' );
	newMeasure( 'Löffel' );
	newMeasure( 'Stück' );
	newType( 'Virgin' );
	newType( 'Fruchtig' );
	newType( 'Cocktail' );
	newType( 'Hart' );
	newPart( 'Nichts' );
}

function newPart( $name, $comment, $type=0 ) {
	db_exec( "INSERT OR IGNORE INTO part (name, comment, type) VALUES (?,?,?);", array( $name, $comment, $type ) );
}

function setPart( $id, $name, $comment, $type ) {
	db_exec( "UPDATE part SET name=?, comment=?, type=? WHERE id=?;", array( $name, $comment, $type, $id ) );
}

function newType( $name ) {
	db_exec( "INSERT OR IGNORE INTO type (name) VALUES (?);", array( $name ) );
}

function newMeasure( $name ) {
	db_exec( "INSERT OR IGNORE INTO measure (name) VALUES (?);", array($name) );
}

function addCocktail( $name, $recipe ) {
	$res = db_exec( "INSERT OR IGNORE INTO cocktail (name, recipe) VALUES (?,?);", array( $name, $recipe ) );
	return getCocktailID( $name );
}

function removeCocktail( $cid ) {
	removeParts( $cid );
	$res = db_exec( "DELETE FROM cocktail WHERE id = ?;", array( $id ) );
}

function setCocktail( $cid, $name, $recipe ) {
	$res = db_exec( "UPDATE cocktail SET name=?, recipe=? WHERE id=?;", array( $name, $recipe, $cid ) );
}

function setCocktailType( $cid, $type ) {
	$res = db_exec( "UPDATE cocktail SET type=? WHERE id=?;", array( $type, $cid ) );
}

function addPart( $cockid, $count, $measure, $part ) {
	db_exec( "INSERT INTO recipe ( cockid, count, measure, part ) VALUES ( ?, ?, ?, ? );",
		array( $cockid, $count, $measure, $part ) );
}

function getTypes( ) {
	$res = db_exec( "SELECT id, name FROM type;" );
	return $res;
}

function getTypeName( $id ) {
	$res = db_exec( "SELECT name FROM type WHERE id = ?;", array( $id ) );
	if( !empty( $res ) ) return $res[0]['name'];
	else return "Unbekannt!";
}

function getMeasures( ) {
	$res = db_exec( "SELECT id, name FROM measure;" );
	return $res;
}

function getMeasure( $id ) {
	$res = db_exec( "SELECT name FROM measure WHERE id = ?;", array( $id ) );
	if( !empty( $res ) ) return $res[0]['name'];
	else return "Unbekannt!";
}

function getParts( ) {
	$res = db_exec( "SELECT id, name, comment, type FROM part ORDER BY name;" );
	return $res;
}

function getPartsByType( $type ) {
	$res = db_exec( "SELECT id, name, comment FROM part WHERE type=? ORDER BY name;", array( $type ) );
	return $res;
}

function removeParts( $id ) {
	$res = db_exec( "DELETE FROM recipe WHERE cockid = ?;", array( $id ) );
}

function getPart( $id ) {
	$res = db_exec( "SELECT id, name, comment, type FROM part WHERE id = ?;", array( $id ) );
	if( !empty( $res ) ) return $res[0];
	else return array( -1, "Unbekannt!", "", -1 );
}

function getPartName( $id ) {
	$res = db_exec( "SELECT name FROM part WHERE id = ?;", array( $id ) );
	if( !empty( $res ) ) return $res[0]['name'];
	else return( "Unbekannt!" );
}

function getPartType( $id ) {
	$res = db_exec( "SELECT type FROM part WHERE id = ?;", array( $id ) );
	if( !empty( $res ) ) return $res[0]['type'];
	else return -1;
}

function getCocktails( ) {
	$res = db_exec( "SELECT * FROM cocktail ORDER BY name;" );
	return $res;
}

function getCocktail( $id ) {
	$res = db_exec( "SELECT id, name, type FROM cocktail WHERE id=?;", array( $id ) );
	if( isset( $res[0] ) ){
		return $res[0];
	} else {
		return -1;
	}
}

function getCocktailName( $cockid ) {
	$res = db_exec( "SELECT name FROM cocktail WHERE id=?;", array( $cockid ) );
	if( isset( $res[0]['name'] ) ) return $res[0]['name'];
	else return "Unbekannter Cocktail!";
}  

function getCocktailType( $cockid ) {
	$type= 1;
	$res = db_exec( "SELECT type FROM cocktail WHERE id=?;", array( $cockid ) );
	if( isset( $res[0]['type'] ) ) $type = $res[0]['type'];
	return getTypeName( $type );
}  

function getCocktailTypeID( $cockid ) {
	$type= 1;
	$res = db_exec( "SELECT type FROM cocktail WHERE id=?;", array( $cockid ) );
	if( isset( $res[0]['type'] ) ) $type = $res[0]['type'];
	return $type;
}  

function getCocktailID( $name ) {
	$res = db_exec( "SELECT id FROM cocktail WHERE name like ?;", array( $name ) );
	if( isset( $res[0]['id'] ) ) return $res[0]['id'];
	else return 0;
}  

function getCocktailRecipe( $cockid ) {
	$res = db_exec( "SELECT recipe FROM cocktail WHERE id=?;", array( $cockid ) );
	if( isset( $res[0]['recipe'] ) ) return $res[0]['recipe'];
	else return "Nummern raten ist dohv!";
}

function getCocktailParts( $cockid ) {
	$res = db_exec( "SELECT measure, count, name, comment, type, part FROM recipe INNER JOIN part ON recipe.part=part.id WHERE recipe.cockid=? ORDER BY part.type ASC,measure ASC,count DESC;", array( $cockid ) );
	return $res;
}

function getCocktailParts2( $cockid ) {
	$res = db_exec( "SELECT * FROM recipe WHERE cockid=?;", array( $cockid ) );
	return $res;
}

function findCocktailsByPart( $partid ) {
	$res = db_exec( "SELECT cockid FROM recipe WHERE part=?;", array( $partid ) );
	return $res;
}

function findCocktailsByParts( $parts ) {
	$result=array();
	foreach( $parts as $part ) {
		if( $part != 1 ) {
			$list = findCocktailsByPart( $part );
			if( isset( $list[0] ) ) {
				foreach( $list as $cocktail ) {
					if( !isset( $result[ $cocktail['cockid'] ] ) ) {
						$result[ $cocktail['cockid'] ] = getCocktail( $cocktail['cockid'] );
						$result[ $cocktail['cockid'] ]['rank'] = 1;
					} else {
						$result[ $cocktail['cockid'] ]['rank']++;
					}
				}
			}
		}
	}
	return $result;
}

function findCocktailsWithout( $noparts ) {
	$result=array();
	$num=0;
	$allcock=getCocktails();
	foreach( $allcock as $cocktail ) {
		$parts=getCocktailParts( $cocktail['id'] );
		$in=0;
		foreach( $parts as $cpart ) {
			foreach( $noparts as $part ) {
				if( $cpart['part'] == $part ) $in=1;
			}
		}
		if( $in == 0 ) { 
			$result[]=$cocktail;
			$num++;
		}
	}
	return $result;
}

function findCocktailsByType( $typeid ) {
	$res = db_exec( "SELECT id,name FROM cocktail WHERE type=?;", array( $typeid ) );
	return $res;
}

function invertParts( $list ) {
	$parts = getParts();
	$result=array();
	foreach( $parts as $part ) $result[$part['id']]=$part['id'];
	foreach( $list as $part ) unset( $result[$part] );
	return $result;	
}

function findCocktailsByTypeAndParts( $type, $available ){
	$notin = invertParts( $available );
	$result=array();
	$num=0;
	$allcock=findCocktailsByType( $type );
	foreach( $allcock as $cocktail ) {
		$parts=getCocktailParts( $cocktail['id'] );
		$in=0;
		foreach( $parts as $cpart ) {
			foreach( $notin as $part ) {
				if( $cpart['part'] == $part ) $in=1;
			}
		}
		if( $in == 0 ) { 
			$result[]=$cocktail;
			$num++;
		}
	}
	return $result;

}
?>
