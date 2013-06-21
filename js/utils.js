/*
checks if value of textfield
is an integer
between minval and maxval
*/

function generalstats_check_integer(object, minval, maxval) {

	var value=object.value;

	if (isNaN(value)) {
		alert('You did not enter a numeric value!');
		return false;
	}

	var parsed_value=parseInt(value, 10);

	if (isNaN(parsed_value) || parsed_value!=value) {
		alert('You did not enter a numeric value!');
		return false;
	}

	if (!isNaN(minval) && parsed_value < minval) {
		alert('Your entry has to be larger or equal than '+minval);
		return false;
	}

	if (!isNaN(maxval) && parsed_value > maxval) {
		alert('Your entry has to be smaller or equal than '+maxval);
		return false;
	}

	return true;

}

/*
toggle related fields
checkbox and array of fields
*/

function generalstats_toggle_related_fields(element, fields, checked) {

	if (element.checked==checked) {
		for (var i=0;i<fields.length;i++) {
			$('generalstats_'+fields[i]).disabled=null;
		}
	}

	else {
		for (var i=0;i<fields.length;i++) {
			$('generalstats_'+fields[i]).disabled='disabled';
		}
	}
}