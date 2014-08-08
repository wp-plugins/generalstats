/*
hide all option-page sections
*/

function generalstats_hide_sections() {
	for (var i=0; i<generalstats_sections.length; i++) {
		jQuery('#generalstats_'+generalstats_sections[i]+'_link').removeClass('current');
		jQuery('#generalstats_'+generalstats_sections[i]).css('display', 'none');
	}
}

/*
opens admin-menu section
*/

function generalstats_open_section(section) {
	generalstats_hide_sections();

	var my_section='';

	if (section.length>0) {
		for (var i=0; i<generalstats_sections.length; i++) {
			if (generalstats_sections[i]==section) {
				my_section=section;
				break;
			}
		}
	}

	if (my_section.length===0)
		my_section=generalstats_sections[0];

	jQuery('#generalstats_'+my_section).css('display', 'block');
	jQuery('#generalstats_'+my_section+'_link').addClass('current');
	jQuery('#generalstats_section').val(my_section);
}

/*
- shows section-links only if menu is visible

- hides settings-page-menu and
displays all settings-page-sections
except drag-and-drop

if viewport < 440px
*/

function generalstats_resize_settings_page() {
	if (jQuery(window).width()<440) {
		if (jQuery('#generalstats_menu').is(':visible')) {
			jQuery('.generalstats_section_link').hide();
			jQuery('.generalstats_section_text').show();

			jQuery('#generalstats_menu').hide();
			jQuery('#generalstats_form_settings > div').show();
			jQuery('#generalstats_drag_and_drop').hide();
		}
	}

	else {
		if (!jQuery('#generalstats_menu').is(':visible')) {
			jQuery('.generalstats_section_text').hide();
			jQuery('.generalstats_section_link').show();

			jQuery('#generalstats_menu').show();
			generalstats_open_section(jQuery('#generalstats_section').val());
		}
	}
}