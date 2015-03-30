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
	if ( amount == undefined ) amount=0;
	reqs++;
	xmlhttp.onreadystatechange=function() {
  		if (xmlhttp.readyState==4 ) {
			if ( xmlhttp.status!=200 ) {
				alert( "Fehler "+xmlhttp.status+" beim setzen!" );
  			}
			reqs--;
			// Update status on screen
			if( e != undefined ) {
				if( reqs != 0 ) e.innerHTML='<b>-&gt; '+reqs+'</b>';
				else e.innerHTML='<b>Fertig</b>';
			}
		}
		// update number and checkmark on screen
		if( n != undefined ) {
       	    n.value=amount
//       	    if( amount == 0 ) c.checked=false;
//       	    else c.checked=true
       	    if( amount == 0 ) c.value=' ';
       	    else c.value='\u2714';
        }
	}
	
	xmlhttp.open("GET", "?cmd=qsetstate&row="+entrynum+"&state="+amount, true);
	xmlhttp.send();
}



function toggle(id,stars){
	var xmlhttp=new XMLHttpRequest();
    var e=document.getElementById('cr'+id )
    var d=document.getElementById('dr'+id )
    var stars=d.title;
    stars=(stars+1)%3;
    
//     alert( "stars="+stars );
    
	xmlhttp.onreadystatechange=function() {
  		if (xmlhttp.readyState==4 ) {
			if ( xmlhttp.status!=200 ) {
				alert( "Fehler "+xmlhttp.status+" beim setzen!" );
  			}else{
  			    switch( stars ) {
  			        case 1:
                        e.style.color='#f00'
                        e.innerHTML="&nbsp;&hearts;";
  			        break;
  			        case 2:
                        e.style.color='#000'
                        e.innerHTML="&nbsp;&#9760;";  			        
  			        break;
  			        default:
                        e.style.color='#000'
                        e.innerHTML="";
                    break;
                }
                d.title=stars;
            }
		}
    }

	xmlhttp.open("GET", "?cmd=qsetrate&cid="+id+"&rate="+stars, true);
	xmlhttp.send();
    
}

function addPartField(){
    var number = document.getElementById('member').value;
    var container = document.getElementById('container');
    while (container.hasChildNodes()) {
        container.removeChild(container.lastChild);
    }
    for (i=0;i<number;i++){
        container.appendChild(document.createTextNode('Member ' + (i+1)));
        var input = document.createElement('input');
        input.type = 'text';
        container.appendChild(input);
        container.appendChild(document.createElement('br'));
    }
}
