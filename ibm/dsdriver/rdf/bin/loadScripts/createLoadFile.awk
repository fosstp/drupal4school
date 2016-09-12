#
# Assumes sorted nt file by subject or by object,
# Reads all hashes for predicate first
# Writes it out using as subject graph and then predicate value pairs,
# placing predicates in the right position based on hashes
#
# Takes as an argument hashFile which contains the list of 3 hashes for the load
# Takes as argument fileType which indicates "quad" if the fileType is n-quads
# Takes as argument discardFile which contains the triple which are not loaded
#
BEGIN {
    columnDelimiter="\t";
    rowDelimiter="\n";
    charDelimiter="\"";

    numColors = 0;
    entry_id = 0;
    while ((getline line < hashFile) > 0) {
	n = split(line, arr, " ");
	for (i = 2; i <= n; i++) {
	    hashes[arr[1]] = hashes[arr[1]] " " arr[i];
	    if (numColors < arr[i]) {
		numColors = arr[i];
	    }
	}  
    }
    
    delete cols;

    currSubj ="";
    id = 0;
    delete row;
    delete graphs;
}


function printEntry(x,  a,str) {
    if (index(x, "\"") == 1 && parseLiteral(x, a) != 0) {
	str = getString(a);
    } else {
	str = fixBrackets(x);
    }
   str = escapeDoubleQuotes(str);
   return charDelimiter hash_string(str,x) charDelimiter;
}

function printSubject(subj,  i,j,k,spill,code) {
    
    for (k in graphs) {
	spill = (row[k] > 1)? 1: 0;
       for (i = 0; i < row[k]; i++) {
	    printf("%s%s%s%s", entry_id, columnDelimiter, printEntry(subj), columnDelimiter) > primaryFile ; 
           entry_id = entry_id + 1;
	    if (setsFor=="object") {
		code = type_code(subj);
		printf("%s%s%s%s%s%s", 
		       is_number(code)? as_number(subj): "", columnDelimiter, 
		       is_date(code)? as_date(subj): "", columnDelimiter, 
		       code, columnDelimiter) > primaryFile;
	    }
	    printf("%s%s%s%s", printEntry(k), columnDelimiter, spill, columnDelimiter) > primaryFile ;
	    if (setsFor=="object") {
		for (j = 0; j < numColors * 2; j++) {
		    printf("%s%s", 
			   ((k, i, j) in cols)? printEntry(cols[k, i, j]): "",
			   columnDelimiter) > primaryFile;
		}
	    } else {
		for (j = 0; j < numColors * 2; j++) {
		    printf("%s%s", 
			   ((k, i, j) in cols)? printEntry(cols[k, i, j]): "",
			   columnDelimiter) > primaryFile;
		    if (j%2 == 1) {
			printf("%s%s", 
			   ((k, i, j) in cols)? type_code(cols[k, i, j]): "", 
			   columnDelimiter) > primaryFile;			
		    }
		}
	    }
	    printf(rowDelimiter) > primaryFile;
	}
    }
}

function printSecondaryRow(entity, property, graph, lid, value) {
    printf("%s%s", printEntry(graph), columnDelimiter) > secondaryFile;

    if (dontUseLids != "yes") {
	printf("%s%s", lid, columnDelimiter) > secondaryFile;
    }

    printf("%s", printEntry(value)) > secondaryFile;
    
    if (setsFor == "subject") {
	printf("%s%s", columnDelimiter, type_code(value)) > secondaryFile;
    }

    if (useEntityInSecondary=="yes") {
	printf("%s%s", columnDelimiter, printEntry(entity)) > secondaryFile;
    }

    if (usePropertyInSecondary=="yes") {
	printf("%s%s", columnDelimiter, printEntry(property)) > secondaryFile;
    }

    print ""  > secondaryFile;
}

function tryInsert(entity, currentRow, predicate, value, graph, n, c, i, filled, type) {
    if (! (predicate in hashes)) {
	return 2;
    }
    else
    {
    n = split(hashes[predicate], c, " ");
    # try inserting into cols at the specified location.  if its occupied with the same pred
    # we need to write to the secondary hash.  If not, we need to write to primary file
    filled = 0;
    for (i = 1; i <=n; i++) {
	if (!((graph, currentRow, 2*c[i]) in cols)) {
	    cols[graph, currentRow, 2*c[i]] = predicate;
	    cols[graph, currentRow, 2*c[i]+1] = value;
	    filled = 1;
	    break;
	} else {
	    # we have this exact same predicate already for this row
	    if (cols[graph, currentRow, 2*c[i]] == predicate) {
		v = cols[graph, currentRow, 2*c[i]+1];
		if (index(v, "lid:") != 1) {
		    # current value is a single value, not an lid...

		    # make an lid
		    id++;	
		    thisLid = "lid:" part ":" id;
		    cols[graph, currentRow, 2*c[i]+1] = "lid:" part ":" id;

		    # save current value in secondary table
		    printSecondaryRow(entity, predicate, graph, thisLid, v);
		} else {   
		    # otherwise, we have an lid to use already
		    thisLid = v;
		}

                # put value into secondary table 
		printSecondaryRow(entity, predicate, graph, thisLid, value);
		filled=1;
		break;
	    }
	}
    }
    return filled;
    }
}

{
    parse_for_elements($0, elts)
    object = elts["object"];
    graph = "DEF";
    if (fileType=="quad") {
	graph = elts["graph"];
	if (trimString(graph) == "") {
	    graph = "DEF";
	}
    }
    
    if (setsFor=="subject") {
	entity = elts["subject"];
	value = object;
    } else {
	entity = object;
	value = elts["subject"];
    }

    if (entity != currSubj) {
	if(currSubj != "")printSubject(currSubj);
	delete cols;
	delete graphs;
	delete row;
	currSubj = entity;
    }

    graphs[graph] = 1;

    if (! (graph in row)) {
	row[graph] = 1;
    }

    done = 0;
    # try inserting into any of the existing rows
    for (i = 0; i < row[graph]; i++) {
       status = tryInsert(entity, i, elts["predicate"], value, graph)
	if (status == 1) {
	    done = 1;
	    break;
	}
	else if( status == 2){
	    if (setsFor=="subject") {
	      print $0 > discardFile;
          }
           done = 1;
	    break;
	}
    }
    if (done != 1) {
	# could not insert this data at all, have to add a new row
	tryInsert(entity, row[graph], elts["predicate"], value, graph);
	row[graph]++;
    }
}
END {
    printSubject(currSubj);
}

