function initializeEmailsTextarea() {
	const emailsTextarea = document.getElementById( 'emails-textarea' );

	if ( ! emailsTextarea ) {
		return;
	}

	emailsTextarea.addEventListener( 'focus', function () {
		this.select();
	} );

	emailsTextarea.style.height = emailsTextarea.scrollHeight + 'px';
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initializeEmailsTextarea );
} else {
	initializeEmailsTextarea();
}
