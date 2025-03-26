send_event( 5 );

function send_event( attempts ) {
    if ( attempts <= 0 ) {
        return;
    }

    if ( event_data && omnisend ) {
        omnisend.push( event_data );
        
        return;
    }

    setTimeout( function() {
        send_event( attempts - 1 );
    }, 100 );
}