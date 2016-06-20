/**
 * set the state of a given item and update the GUI with the
 * current status.
 */
function setnum( entrynum, amount ) {
	// status line (if any)
	var e=document.getElementById('save');
	// number and checkmark widgets (if any) 
	var n=document.getElementById('num'+entrynum )
	var c=document.getElementById('check'+entrynum )
	// may happen on ported databases
	if ( amount === undefined ) amount=0;
	reqs++;
	xmlhttp.onreadystatechange=function() {
        if (xmlhttp.readyState==4 ) {
			if ( xmlhttp.status!=200 ) {
				alert( "Fehler "+xmlhttp.status+" beim setzen!" );
            }
			reqs--;
			// Update status on screen
			if( e !== undefined ) {
				if( reqs !== 0 ) e.innerHTML='<b>-&gt; '+reqs+'</b>';
				else e.innerHTML='<b>Fertig</b>';
			}
		}
		// update number and checkmark on screen
		if( n !== undefined ) {
            n.value=amount;
//          if( amount == 0 ) c.checked=false;
//          else c.checked=true
            if( amount === 0 ) c.value=' ';
            else c.value='\u2714';
        }
	};
	
	xmlhttp.open("GET", "?cmd=qsetstate&row="+entrynum+"&state="+amount, true);
	xmlhttp.send();
}

/**
 * cycle through stars
 */
function toggle(id){
	var xmlhttp=new XMLHttpRequest();
    var e=document.getElementById('cr'+id );
    var stars;// =e.title;
    stars=(parseInt(e.title,10)+1)%6;

//    alert( "stars="+stars );
    
	xmlhttp.onreadystatechange=function() {
        if (xmlhttp.readyState==4 ) {
			if ( xmlhttp.status!=200 ) {
				alert( "Fehler "+xmlhttp.status+" beim setzen!" );
            }else{
                switch( stars ) {
                    case 1:
                        e.style.color='#300';
                        e.innerHTML="&nbsp;&#9760;&#9734;&#9734;&#9734;&#9734;";
                    break;
                    case 2:
                        e.style.color='#600';
                        e.innerHTML="&nbsp;&#9733;&#9733;&#9734;&#9734;&#9734;";
                    break;
                    case 3:
                        e.style.color='#900';
                        e.innerHTML="&nbsp;&#9733;&#9733;&#9733;&#9734;&#9734;";
                    break;
                    case 4:
                        e.style.color='#b00';
                        e.innerHTML="&nbsp;&#9733;&#9733;&#9733;&#9733;&#9734;";
                    break;
                    case 5:
                        e.style.color='#f00';
                        e.innerHTML="&nbsp;&#9733;&#9733;&#9733;&#9733;&#9733;";
                    break;
                    default:
                        e.style.color='#000';
                        e.innerHTML="&nbsp;&#9734;&#9734;&#9734;&#9734;&#9734;";
                    break;
                }
                e.title=stars;
            }
		}
    };

    e.style.color='#000';
    e.innerHTML="&nbsp<b>-----</b>";
	xmlhttp.open("GET", "?cmd=qsetrate&cid="+id+"&rate="+stars, true);
	xmlhttp.send();
    
}

function addPartField(){
    var number    = parseInt( document.getElementById('lines').value, 10 );
    var container = document.getElementById('parts');
    var newpart   = document.getElementById('parts0').cloneNode(true);
    var buf;
    
    newpart.id='parts'+number;
    buf=newpart.children[0];
    buf.name="count"+number;
    buf.value=1;
    buf=newpart.children[1];
    buf.name="measure"+number;
    buf.value=1;
    buf=newpart.children[2];
    buf.name="part"+number;
    buf.value=1;
    document.getElementById('lines').value=(number+1);
    container.appendChild(newpart);
}
