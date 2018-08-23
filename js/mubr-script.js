jQuery( document ).ready( function( $ ) {
    
    //on textarea focus, select all
    $('#emails-textarea').focus( function() {
        this.select();
    });
});

