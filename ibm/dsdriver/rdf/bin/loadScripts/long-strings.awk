BEGIN {
    stringOut = (pawk == "yes")? longStringFile "." part: longStringFile;
	cmd = "java com.ibm.rdf.store.internal.loader.LongStringHasher " cutoff;
    table = "sort | uniq > " "\"" stringOut "\""
}

function hash_string(str,entry,  result,stuff,str2) {
   if (length(str) < cutoff) {
	return str;
    } else {
	print str |& cmd;
	cmd |& getline result;
	if (str != result) {
		str = fixBrackets(str);
	    initial = substr(str,0,2000);
	    overflow = substr(str,2000);
	    
	    print result "\t" initial "\t" type_code(entry) "\t" overflow |& table;
	    return result;
	} else {
		return result;
	}
    }
}
