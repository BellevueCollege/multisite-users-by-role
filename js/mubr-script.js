jQuery( document ).ready( function( $ ) {
    
    //allow using left click to select multiple options
    $('select#multisite_user_list_selected_role option').mousedown( function(e) {
        e.preventDefault();
        this.selected = !this.selected;
        return false;
    });

    //on textarea focus, select all
    $('#emails-textarea').focus( function() {
        this.select();
    });
});

