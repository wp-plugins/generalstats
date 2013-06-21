/*
moves an element in a drag and drop list
one position up

modified by Nikk Folts, http://www.nikkfolts.com/
*/

function generalstats_move_element_up_for_list(list, row) {
	return generalstats_move_row(list, row, 1);
}

/*
moves an element in a drag and drop list
one position down

modified by Nikk Folts, http://www.nikkfolts.com/
*/

function generalstats_move_element_down_for_list(list, row) {
	return generalstats_move_row(list, row, -1);
}

/*
moves an element in a drag and drop list
one position

modified by Nikk Folts, http://www.nikkfolts.com/
*/

function generalstats_move_row(list, row, dir) {
	var sequence=Sortable.sequence(list);
	var found=false;

	/*
	move only, if there is more than
	one element in the list
	*/

	if (sequence.length>1) for (var j=0; j<sequence.length; j++) {

		/*
		element found
		*/

		if (sequence[j]==row) {
			found=true;

			var i = j - dir;

			if (i >= 0 && i <= sequence.length) {
				var temp=sequence[i];
				sequence[i]=row;
				sequence[j]=temp;
				break;
			}
		}
	}

	Sortable.setSequence(list, sequence);

	return found;
}

/*
handles moving up for both lists
*/

function generalstats_move_element_up(key) {

	/*
	try to move the element in first list
	*/

	if (generalstats_move_element_up_for_list('generalstats_list_selected', key)===false) {

		/*
		if we didn't find it, try
		to move the element in the
		second list
		*/

		generalstats_move_element_up_for_list('generalstats_list_available', key);
	}

	/*
	update the lists
	*/

	generalstats_update_drag_and_drop_lists();
}

/*
handles moving down for both lists
*/

function generalstats_move_element_down(key) {

	/*
	try to move the element in first list
	*/

	if (generalstats_move_element_down_for_list('generalstats_list_selected', key)===false) {

		/*
		if we didn't find it, try
		to move the element in the
		second list
		*/

		generalstats_move_element_down_for_list('generalstats_list_available', key);
	}

	/*
	update the lists
	*/

	generalstats_update_drag_and_drop_lists();
}

/*
initializes or reinitializes the
drag_and_drop lists
*/

function generalstats_initialize_drag_and_drop() {

	Sortable.create("generalstats_list_selected", {
		dropOnEmpty:true,
		containment:["generalstats_list_selected","generalstats_list_available"],
		constraint:false,
		onUpdate:function(){ generalstats_update_drag_and_drop_lists(); }
	});

	/*
	as we have two lists,
	the second list will be
	automatically updated
	if the first list is updated
	*/

	Sortable.create("generalstats_list_available", {
		dropOnEmpty:true,
		containment:["generalstats_list_selected","generalstats_list_available"],
		constraint:false
	});
}

/*
returns the sorted ids of a
drag_and_drop list
*/

function generalstats_get_sorted_ids(list) {

	/*
	get current stats order
	*/

	var list=escape(Sortable.sequence('generalstats_list_'+list));

	var sorted_ids = [-1];

	/*
	if we got at least one element
	in the list,
	retrieve the sorted_ids
	*/

	if (list && list.length>0) {
		var maybe_sorted_ids = unescape(list).split(',');
		var ret_sorted_ids=[];

		/*
		loop through all ids and
		filter out empty elements
		*/

		for (var i=0;i<maybe_sorted_ids.length;i++) {
			if (maybe_sorted_ids[i] && maybe_sorted_ids[i]>-1) {
				ret_sorted_ids.push(maybe_sorted_ids[i]);
			}
		}

		if (ret_sorted_ids.length>0)
			sorted_ids=ret_sorted_ids;
	}

	return sorted_ids;
}

/*
sets list height of drag_and_drop list
according to the number of elements
*/

function generalstats_set_list_height(list, sorted_ids) {

	var element_height=32;

	/*
	calculate new list height of list_selected
	*/

	var list_length=element_height;

	if (sorted_ids.length>1)
		list_length=sorted_ids.length*element_height;

	/*
	set new list height
	*/

	$('generalstats_list_'+list).style.height = list_length+'px';
}

/*
drag and drop lists update function
updates stats textfields
*/

function generalstats_update_drag_and_drop_lists() {

	var selected_sorted_ids=generalstats_get_sorted_ids('selected');

	/*
	clear all previously set values
	*/

	for (var i = 0; i < generalstats_keys.length; i++) {
		$('generalstats_stat_pos_'+generalstats_keys[i]).value = "";
	}

	/*
	set new values
	in textfields
	*/

	for (var i = 0; i < selected_sorted_ids.length; i++) {

		/*
		looks up keys array for matching index
		*/

		for (var j = 0; j < generalstats_keys.length; j++) {

			/*
			match found
			*/

			if (generalstats_keys[j]==selected_sorted_ids[i]) {
				$('generalstats_stat_pos_'+generalstats_keys[j]).value = i+1;
			}
		}
	}

	generalstats_set_list_height('selected', selected_sorted_ids);

	var available_sorted_ids=generalstats_get_sorted_ids('available');

	generalstats_set_list_height('available', available_sorted_ids);

}

/*
load selected field in edit panel
*/

function generalstats_populate_drag_and_drop (key) {
	generalstats_populate_drag_and_drop_set_value(key, false);
}

/*
load selected field in edit panel
*/

function generalstats_populate_drag_and_drop_default () {
	generalstats_populate_drag_and_drop_set_value ($('generalstats_edit_selected_stat').value, true);
}

/*
populate selected field with new value
if reset is set to false, the user's value
will be loaded
*/

function generalstats_populate_drag_and_drop_set_value (key, reset) {

	/*
	hide message
	*/

	$('generalstats_edit_success_label').style.display='none';

	for (var j = 0; j < generalstats_keys.length; j++) {
		if (generalstats_keys[j]==key) {
			$('generalstats_edit_selected_stat').value=key;

			/*
			create label with stat-name
			*/

			Element.replace($('generalstats_edit_label'), '<span id="generalstats_edit_label">Text for '+generalstats_fields[j]+'</span>');

			/*
			load stat's custom name
			*/

			var new_value=generalstats_fields[j];

			if (!reset)
				new_value=$('generalstats_stat_desc_'+generalstats_keys[j]).value;

			$('generalstats_edit_text').value = new_value;

			/*
			assure display of div
			*/
			$('generalstats_edit').style.display='block';

			$('generalstats_edit_text').focus();
			break;
		}
	}
}

/*
apply changes of currently selected stat
*/

function generalstats_change_entry() {

	var field_name= $('generalstats_edit_selected_stat').value;

	if (field_name.length>0) {
		$('generalstats_stat_desc_'+field_name).value = $('generalstats_edit_text').value;
		new Effect.Highlight($('generalstats_edit'),{startcolor:'#30df8b'});
		new Effect.Appear($('generalstats_edit_success_label'));

		/*
		adopt drag and drop table
		*/

		for (var j = 0; j < generalstats_keys.length; j++) {
			if (generalstats_keys[j]==field_name) {
				$('generalstats_stat_'+generalstats_keys[j]).childNodes[2].nodeValue= $('generalstats_edit_text').value+' ('+generalstats_fields[j]+')';
				new Effect.Highlight($('generalstats_stat_'+generalstats_keys[j]),{startcolor:'#30df8b'});

				break;
			}
		}

	}

	else {
		alert('Please click on the desired list field to adopt setting!');
	}

}