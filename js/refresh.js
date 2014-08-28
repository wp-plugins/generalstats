/*
conducts an Ajax refresh

params (used only locally)
 - field: the id of the field which should be updated
 - fields: all elements with the given class-name will be updated
 - compare_string: this string will be used for comparison with the result attribute in the json-response (starting at position 0)
 - callback_init
 - callback_finished(json/null)
 - callback_error

query_params (will be transferred to the server)
 - action: corresponding wp_ajax or wp_ajax_nopriv hook
 - _ajax_nonce: WordPress nonce
 - query_string: function parameters, urlencoded

error-messages:

-2 no result
-3 result does not match compare_string
-4 transport error
*/

function generalstats_refresh(params, query_params) {
	jQuery.ajax({
		url: generalstats_refresh_settings.ajax_url,
		cache: false,
		type: 'POST',
		data: query_params.toQueryString(),
		dataType: 'json',

		beforeSend: function(XMLHttpRequest) {
			XMLHttpRequest.params=params;
			XMLHttpRequest.query_params=query_params;

			if (params.containsKey('callback_init') && params.get('callback_init')!==null) {
				var callback_init_function=params.get('callback_init');
				window[callback_init_function()];
			}
		},

		success: function(data, textStatus, XMLHttpRequest) {
			var json=data;

			try {
				if (!generalstats_is_undefined(json._ajax_nonce) && json._ajax_nonce!==null && json._ajax_nonce.length)
					XMLHttpRequest.query_params.put('_ajax_nonce', json._ajax_nonce);

				var blocks=new jQuery();

				if (XMLHttpRequest.params.containsKey('fields') && XMLHttpRequest.params.get('fields')!==null && XMLHttpRequest.params.get('fields').length)
					blocks=jQuery(XMLHttpRequest.params.get('fields'));

				var field=new jQuery();

				if (XMLHttpRequest.params.containsKey('field') && XMLHttpRequest.params.get('field')!==null && XMLHttpRequest.params.get('field').length)
					field=jQuery('#'+XMLHttpRequest.params.get('field'));

				if (blocks.length>0 || field.length>0) {
					if (generalstats_is_undefined(json.result) || json.result===null || !json.result.length)
						throw -2;

					var result=json.result;

					if (XMLHttpRequest.params.containsKey('compare_string') && XMLHttpRequest.params.get('compare_string')!==null && XMLHttpRequest.params.get('compare_string').length && result.indexOf(XMLHttpRequest.params.get('compare_string'))!==0)
						throw -3;

					blocks.replaceWith(result);
					field.replaceWith(result);
				}
			}

			catch(error) {
				if (XMLHttpRequest.params.containsKey('callback_error') && XMLHttpRequest.params.get('callback_error')!==null) {
					var callback_error_function=XMLHttpRequest.params.get('callback_error');
					window[callback_error_function(error)];
				}
			}
		},

		error: function(XMLHttpRequest, textStatus, errorThrown) {
			if (XMLHttpRequest.params.containsKey('callback_error') && XMLHttpRequest.params.get('callback_error')!==null) {
				var callback_error_function=XMLHttpRequest.params.get('callback_error');
				window[callback_error_function(-4)];
			}
		},

		complete: function(XMLHttpRequest, textStatus) {
			if (XMLHttpRequest.params.containsKey('callback_finished') && XMLHttpRequest.params.get('callback_finished')!==null) {
				var callback_finished_function=XMLHttpRequest.params.get('callback_finished');

				var json;

				try {
					json=jQuery.parseJSON(XMLHttpRequest.responseText);
				}

				catch(error) {
					json=null;
				}
		
				window[callback_finished_function(json)];
			}
		}
	});

}

function generalstats_refresh_create_params(field, compare_string) {
	var params=new Hashtable();

	params.put('compare_string', compare_string);
	params.put('field', field);

	return params;
}

function generalstats_refresh_create_query_params_basis(_ajax_nonce, query_string) {
	var query_params=new Hashtable();

	query_params.put('_ajax_nonce', _ajax_nonce);
	query_params.put('query_string', query_string);

	return query_params;
}

function generalstats_refresh_create_query_params_output(_ajax_nonce, query_string) {
	var query_params=generalstats_refresh_create_query_params_basis(_ajax_nonce, query_string);

	query_params.put('action', 'generalstats_output');

	return query_params;
}

function generalstats_refresh_create_query_params_count(_ajax_nonce, query_string) {
	var query_params=generalstats_refresh_create_query_params_basis(_ajax_nonce, query_string);

	query_params.put('action', 'generalstats_count');

	return query_params;
}

function generalstats_register_refresh(params, query_params) {
	window.setInterval(function(){
			generalstats_refresh(params, query_params);
		},
		parseInt(generalstats_refresh_settings.refresh_time, 10)*1000
	);
}

function generalstats_initiate_refresh(params, query_params) {
	jQuery(window).load(function(){
		generalstats_register_refresh(params, query_params);
	});
}

/*
check if variable is undefined
*/

function generalstats_is_undefined(myvar) {
	return (myvar===undefined);
}

var generalstats_params=new Hashtable();

var generalstats_query_params=new Hashtable();

jQuery(window).load(function(){
	if (jQuery('div.generalstats-refreshable-output').length>0) {
		generalstats_params.put('compare_string', '<div class="generalstats-refreshable-output"');

		generalstats_params.put('fields', 'div.generalstats-refreshable-output');

		generalstats_query_params.put('action', 'generalstats_output');

		generalstats_query_params.put('_ajax_nonce', generalstats_refresh_settings._ajax_nonce);

		generalstats_register_refresh(generalstats_params, generalstats_query_params);
	}
});