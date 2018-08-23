jQuery( document ).ready( function( $ ) {

    //on textarea focus, select all
    $('#emails-textarea').focus( function() {
        this.select();
    });

    //resize textarea to fit content
    $("#emails-textarea").height( $("#emails-textarea")[0].scrollHeight );
});

