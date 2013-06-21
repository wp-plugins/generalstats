/*
hide all option-page sections
*/

function generalstats_hide_sections() {
	for (var i=0;i<generalstats_sections.length;i++) {
		$('generalstats_'+generalstats_sections[i]).style.display="none";
		$('generalstats_'+generalstats_sections[i]+'_link').className="";
	}
}

/*
opens admin-menu section
*/

function generalstats_open_section(section) {
	generalstats_hide_sections();

	var my_section='';

	if (section.length>0) {
		for (var i=0;i<generalstats_sections.length;i++) {
			if (generalstats_sections[i]==section) {
				my_section=section;
				break;
			}
		}
	}

	if (my_section.length===0)
		my_section=generalstats_sections[0];

	$('generalstats_'+my_section).style.display="block";
	$('generalstats_'+my_section+'_link').className="current";
	$('generalstats_section').value=my_section;
}