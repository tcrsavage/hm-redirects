jQuery( document ).ready( function( $ ) { 

	 // sortable item args
	var sortableItemArgs = {

    	revert: true,
    	
    	tolerance: 'pointer',

    	placeholder: 'sortable-placeholder',
    	
    	forceHelperSize: true,
    	
    	handle: '.dragger'
    	
    };
    
    // make the redirect list sortable
    $( ".wps301-sortable" ).sortable( sortableItemArgs );
    
    // if both new input fields are populated, allow the user to add the new entry to the list
    $( document ).on( 'keydown', '#new-item input', function( e ) { 
    	
    	var wp301_vals = new Array();
    	
    	$( this ).closest( 'tr' ).find( 'input' ).each( function ( index ) { 

    		wp301_vals[index] = jQuery( this ).val(); 
    	
    	} );
    	
    	if ( wp301_vals[0].length > 1  && wp301_vals[1].length > 1 )
    		$( this ).closest( 'tr' ).addClass( 'addable' );
    	
    	else
    		$( this ).closest( 'tr' ).removeClass( 'addable' );	
    		
    } );   
    
    //Switch out the values from the new entries into a new table row and insert into the table
    $( document ).on( 'click', '#new-item.addable #wps301-addnew', function( e ) { 
     	
     	var wp301_vals = new Array();
    	
    	$( this ).closest( 'tr' ).find( 'input' ).each( function ( index ) { 

    		wp301_vals[index] = jQuery( this ).val(); 
    	
    	} );
    	
    	var wp301_html = '<tr style="display: none;">' 
    					+   '<td class="dragger request">' 
    				    +	    '<input type="text" name="301_redirects[request][]" value="' + wp301_vals[0] +'" />'
    					+   '</td>' 
    					+   '<td class="spacer">&raquo;</td>'
    					+   '<td class="destination">'
    					+		'<div class="wps301-delete"></div>'	 
    					+       '<input type="text" name="301_redirects[destination][]" value="' + wp301_vals[1] +'" />'
    				    +	'</td>'
    					+'</tr>';
						
    	$( '.wps301-sortable' ).append( wp301_html );

    	$( '.wps301-sortable tr' ).last().show('slow', 'swing');
   
    	
    	$( this ).closest( 'tr' ).find( 'input' ).each( function ( index ) { 

    		jQuery( this ).val( '' ).trigger('keydown'); 
    	
    	} );
     
    } );
    
    $( document ).on( 'click', '.wps301-delete', function( e ) { 
    
    	$( this ).closest( 'tr' ).toggle( 'slow', 'swing', function () {
    	
    		$( this ).closest( 'tr' ).remove();
    	
    	} );
    
    } );
    

} );