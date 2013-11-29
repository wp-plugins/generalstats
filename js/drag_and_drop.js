/*
moves an element
in a list
one position up
*/

function generalstats_move_element_up(row) {

	/*
	move the element up
	*/

	var current=jQuery('#generalstats_stat_'+row);
	current.prev().before(current);

	/*
	update the lists
	*/

	generalstats_update_drag_and_drop_lists();
}

/*
moves an element
in a list
one position down
*/

function generalstats_move_element_down(row) {

	/*
	move the element down
	*/

	var current=jQuery('#generalstats_stat_'+row);
	current.next().after(current);

	/*
	update the lists
	*/

	generalstats_update_drag_and_drop_lists();
}

/*
initializes the
drag_and_drop lists
*/

function generalstats_initialize_drag_and_drop() {

	/*
	add sortable to
	both lists
	*/

	jQuery(function() {
		jQuery('#generalstats_list_selected, #generalstats_list_available').sortable({
			connectWith: '.generalstats_sortablelist'
		}).disableSelection();
	});

	/*
	add event handlers
	to watch update
	on list_selected

	- stop
	- receive
	- remove
	*/

	jQuery('#generalstats_list_selected').on('sortstop sortreceive sortremove', function() {
		generalstats_update_drag_and_drop_lists();
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

	var maybe_sorted_ids=jQuery('#generalstats_list_'+list).sortable('toArray');

	var sorted_ids=[-1];

	/*
	if we got at least one element
	in the list,
	retrieve the sorted_ids
	*/

	if (maybe_sorted_ids && maybe_sorted_ids.length>0) {
		var ret_sorted_ids=[];

		/*
		loop through all ids and
		filter out empty elements
		*/

		for (var i=0; i<maybe_sorted_ids.length; i++) {
			maybe_sorted_ids[i]=maybe_sorted_ids[i].replace('generalstats_stat_', '');

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

	jQuery('#generalstats_list_'+list).height(list_length);
}

/*
drag and drop lists update function
updates stats textfields
*/

function generalstats_update_drag_and_drop_lists() {

	/*
	get sorted ids of
	list selected
	*/

	var selected_sorted_ids=generalstats_get_sorted_ids('selected');

	/*
	clear all previously set values
	*/

	for (var i=0; i<generalstats_keys.length; i++)
		jQuery('#generalstats_stat_pos_'+generalstats_keys[i]).val('');

	/*
	set new values
	in textfields
	*/

	for (var i=0; i<selected_sorted_ids.length; i++) {

		/*
		look up keys array for matching index
		*/

		for (var j=0; j<generalstats_keys.length; j++) {

			/*
			match found
			*/

			if (generalstats_keys[j]==selected_sorted_ids[i]) {
				jQuery('#generalstats_stat_pos_'+generalstats_keys[j]).val(i+1);
			}
		}
	}

	generalstats_set_list_height('selected', selected_sorted_ids);

	var available_sorted_ids=generalstats_get_sorted_ids('available');

	generalstats_set_list_height('available', available_sorted_ids);
}

/*
load current value
for selected field
in edit panel
*/

function generalstats_populate_drag_and_drop(key) {
	generalstats_populate_drag_and_drop_set_value(key, false);
}

/*
load default
for selected field
in edit panel
*/

function generalstats_populate_drag_and_drop_default() {
	generalstats_populate_drag_and_drop_set_value(jQuery('#generalstats_edit_selected_stat').val(), true);

	generalstats_change_entry();
}

/*
populate selected field with new value
if reset is set to false, the user's value
will be loaded
*/

function generalstats_populate_drag_and_drop_set_value(key, reset) {
	jQuery('.generalstats_sortablelist').removeClass('generalstats_sortablelist_active');

	for (var i=0; i<generalstats_keys.length; i++) {
		if (generalstats_keys[i]==key) {

			/*
			mark entry in list
			*/

			jQuery('#generalstats_stat_'+key).addClass('generalstats_sortablelist_active');

			/*
			remember currently selected stat
			*/

			jQuery('#generalstats_edit_selected_stat').val(key);

			/*
			replace stat-name in label
			*/

			jQuery('#generalstats_edit_label').text('Text for '+generalstats_fields[i]);

			/*
			load stat's default name
			*/

			var new_value=generalstats_fields[i];

			/*
			load stat's custom name
			*/

			if (!reset)
				new_value=jQuery('#generalstats_stat_desc_'+generalstats_keys[i]).val();

			jQuery('#generalstats_edit_text').val(new_value);

			/*
			display div
			*/

			jQuery('#generalstats_edit').show(500);
			jQuery('#generalstats_edit_text').focus();

			break;
		}
	}
}

/*
apply changes of currently selected stat
*/

function generalstats_change_entry() {
	var field_name=jQuery('#generalstats_edit_selected_stat').val();

	if (field_name.length>0) {

		/*
		adopt field-value in list
		*/

		jQuery('#generalstats_stat_desc_'+field_name).val(jQuery('#generalstats_edit_text').val());
		jQuery('#generalstats_edit').effect('highlight', {color:'#30df8b'}, 1000);

		/*
		adopt drag and drop table
		*/

		for (var i=0; i<generalstats_keys.length; i++) {
			if (generalstats_keys[i]==field_name) {
				jQuery('#generalstats_stat_'+generalstats_keys[i]+' span').html(jQuery('#generalstats_edit_text').val()+' ('+generalstats_fields[i]+')');
				jQuery('#generalstats_stat_'+generalstats_keys[i]).effect('highlight', {color:'#30df8b'}, 1000);

				break;
			}
		}

	}

}