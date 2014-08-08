/*
displays or removes an inline error-message
*/

function generalstats_inline_error(element, message, error) {
	element.siblings().remove('.error');

	if (error)
		element.after('<div class="error">'+message+'</div>');
}

/*
checks if value of textfield
is an integer
between minval and maxval
*/

function generalstats_check_integer(element, minval, maxval) {
	var value=element.val();

	if (isNaN(value)) {
		generalstats_inline_error(element, 'You did not enter a numeric value.', true);
		return false;
	}

	var parsed_value=parseInt(value, 10);

	if (isNaN(parsed_value) || parsed_value!=value) {
		generalstats_inline_error(element, 'You did not enter a numeric value.', true);
		return false;
	}

	if (!isNaN(minval) && parsed_value < minval) {
		generalstats_inline_error(element, 'Your entry has to be larger or equal than '+minval+'.', true);
		return false;
	}

	if (!isNaN(maxval) && parsed_value > maxval) {
		generalstats_inline_error(element, 'Your entry has to be smaller or equal than '+maxval+'.', true);
		return false;
	}

	generalstats_inline_error(element, '', false);

	return true;
}

/*
toggle related fields
checkbox and array of fields
*/

function generalstats_toggle_related_fields(element, fields, checked) {
	if (element.prop('checked')==checked) {
		for (var i=0; i<fields.length; i++) {
			jQuery('#generalstats_'+fields[i]).prop('disabled', false);
		}
	}

	else {
		for (var i=0; i<fields.length; i++) {
			jQuery('#generalstats_'+fields[i]).prop('disabled', true);
		}
	}
}