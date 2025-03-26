identifyContact( 5 );

function identifyContact( attempts ) {
    if ( attempts <= 0 ) {
        return;
    }

    if ( omnisendIdentifiers && omnisend && omnisend.identifyContact ) {
        omnisend.identifyContact( omnisendIdentifiers );

        return;
    }

    setTimeout( function() {
        identifyContact( attempts - 1 );
    }, 100 );
}