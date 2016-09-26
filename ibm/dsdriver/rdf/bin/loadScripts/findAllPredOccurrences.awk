BEGIN {
    delete predCounts;
    delete preds;
    currentEntity ="start token";
    if (typeTable != "") {
	type_cmd = "sort | uniq > " "\"" typeTable "\"";
	locale_cmd = "sort | uniq > " "\"" localeTable "\"";
    }
}

function update_table(str) {
    for(g in graphs) {
	str = "";
	for (i in preds) {
	    if (graph_preds[i, g] == 1) {
		str = str "|" i ;
	    }
	}
	if (str in predCounts) {
	    predCounts[str]++;
	} else {
	    if (str != "") {
		predCounts[str] = 1;
	    }
	}
    }
}

{
    parse_for_elements($0, elts);

    if (setsFor == "subject") {
	thisEntity = elts["subject"];
    } else {
	thisEntity = elts["object"];
    }

    if (fileType=="quad") {
	graph = elts["graph"];
	if (trimString(graph) == "") {
	    graph = "DEF";
	}
    } else {
	graph = "DEF";
    }

    if (thisEntity != currentEntity) {
	if (currentEntity != "start token") {
	    update_table();
	}
	delete preds;
	delete graphs;
	delete graph_preds;
	currentEntity = thisEntity;
    }

    preds[elts["predicate"]] = 1;
    graphs[graph]=1;	
    graph_preds[elts["predicate"], graph] = 1; 

    if (setsFor == "object") {
    if (typeTable != "") {
	if (parseLiteral(elts["object"], literal_elts) != 0) {
	    if (getType(literal_elts) != "") {
			if( type_codes[getType(literal_elts)] == "" ){
		print getType(literal_elts) | type_cmd;
			}
	    }
	    if (getLanguage(literal_elts) != "") {
			if( type_codes[getLanguage(literal_elts)] == "" ){
		print getLanguage(literal_elts) | locale_cmd;
			}
	    }
	}
    }
    }
}

END {
    update_table();
    for (i in predCounts) {
	n = split(i, arr, "|");
	print (n-1), predCounts[i], i;
    }
}
