BEGIN {
    current_entity = "";
    current_graph = "";

    this_graph = "";
    this_entity = "";

    line = 0;

    task_number = 0;
	file_name = "";
}

{
    if (line >= granularity) {
	parse_for_elements($0, elts);

	if (splitBy == "subject") {
	    this_entity = elts["subject"];
	} else {
	    this_entity = elts["object"];
	}
	
	if (fileType == "quad") {
	    this_graph = elts["graph"];
	} else {
	    this_graph = "DEF";
	}

	if (current_entity == "") {
	    current_entity = this_entity;
	    current_graph = this_graph;
	} else {
	    if (this_entity != current_entity || this_graph != current_graph) {
		current_entity = "";
		current_graph = "";
		line = 0;
		task_number++;
		if (task_number >= degree) {
		    task_number = 0;
		}
	    }
	}
    }

	file_name = resultName "." task_number ;
	print $0 > file_name;
    line++;
}
