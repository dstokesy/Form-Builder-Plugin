$(function() {
	
	$(document).on('keyup', '.js-name-safe input', function(){
	    var $this = $(this),
	        value = $this.val();

	    // replace spaces with underscores
	    value = value.replace(new RegExp(' ', 'g'), '_');

	    // make lowercase
	    value = value.toLowerCase();

	    $this.val(value);
	});

});