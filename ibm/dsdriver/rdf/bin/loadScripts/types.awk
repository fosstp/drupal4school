BEGIN {
    while ((getline line < typeCodeFile) > 0) {
	split(line, arr, " ");
	type_codes[arr[1]] = arr[2];
    }
}

function is_number(type) {
    return (type >= type_codes["DATATYPE_NUMERICS_IDS_START"] && type <= type_codes["DATATYPE_NUMERICS_IDS_END"])? 1: 0;
}

function as_number(entry, a) {
    if (is_number(type_code(entry)) != 0) {
	parseLiteral(entry, a);
	return getString(a);
    }
}

function is_date(type) {
    return (type == type_codes["http://www.w3.org/2001/XMLSchema#dateTime"])? 1: 0;
}

function get_date(input) {
    
	#Expected input format yyyy-mm-ddThh:mm:ss(.s+)?(zzzzzz)?
	#DB2 does not support negetive dates
	
	#split by date and time information  by 'T'
    split(input,datetimearray,"T");
	datestring = datetimearray[1];
	
	#split Time and Timezone information by ( Z / z / + / - )
    split(datetimearray[2],timearray, "[zZ+-]");
    timestring = timearray[1];
    zonesign = substr(datetimearray[2],length(timestring)+1,1);
    timezone = timearray[2];
    
	#Flag to indicate the parsing was successful
    status = 0;

	#Adjust the time based on timezone
    if (zonesign  == "-"){

		hourstoadd = substr(timezone,1,2);
		minutestoadd = substr(timezone,4,2);
		split(timestring,timecomponents,":");
		
		## Add minutes
		timecomponents[2] = timecomponents[2] + minutestoadd;
		if(timecomponents[2] > 59 ){
			timecomponents[2] = timecomponents[2] - 60;
			timecomponents[1] = timecomponents[1] + 1;
		}

		## Add hours
		timecomponents[1] = timecomponents[1] + hourstoadd;
		if(timecomponents[1] > 23) {
			timecomponents[1] = timecomponents[1]  - 24;
			## call date function
			status = 1;
		}
      
		timestring =  timecomponents[1] ":" timecomponents[2] ":" timecomponents[3]
    }

    if (zonesign  == "+"){

		hourstoadd = substr(timezone,1,2);
		minutestoadd = substr(timezone,4,2);
		split(timestring,timecomponents,":");
		timecomponents[2] = timecomponents[2] - minutestoadd;

		## Add minutes
		if(timecomponents[2] < 0){
			timecomponents[2] = 60 - timecomponents[2];
			timecomponents[1] = timecomponents[1] - 1;
		}

		## Add hours
		timecomponents[1] = timecomponents[1] - hourstoadd;
		if(timecomponents[1] < 0) {
			timecomponents[1] = 24 - timecomponents[1];
			## call date function
			status = 1;
		}
		
		timestring =  timecomponents[1] ":" timecomponents[2] ":" timecomponents[3]
    }

	## Replace ':' with '.' in the time
    gsub(/:/, ".", timestring)
   
    ## Separate date and time by '-'
    newstring = datestring "-" timestring  
      
	## Call date command if parsing was unsuccessful
    if( status == 1 ){
		cmd = "TZ=\"GMT\" date --date=\"" input "\" +\"%F-%H.%M.%S.%N\""
		cmd | getline date;
		close(cmd);
		newstring = substr(date, 1, length(date)-3);
    }

	## Return
    return newstring;
}


function as_date(entry, cmd, a) {
    if (is_date(type_code(entry)) != 0) {
		parseLiteral(entry, a);
		return get_date(getString(a));
    }
}

function type_code(entry, stuff) {
    if (parseLiteral(entry, stuff) != 0) {
	if (getType(stuff) != "") {
	    return type_codes[getType(stuff)];
	} else if (getLanguage(stuff) != "") {
	    return type_codes[getLanguage(stuff)];
	} else {
	    return type_codes["SIMPLE_LITERAL_ID"];
	}
    } else {
	if (index(entry, "_:") == 1) {
	    return type_codes["BLANK_NODE_ID"];
	} else if (entry == "") {
	    return type_codes["NONE_ID"];
	} else if (index(entry, "lid:") == 1) {
	    return -1;
	} else {
	    return type_codes["IRI_ID"];
	}
    }
}

function checkForUnknownType(entry,stuff) {
	if (parseLiteral(entry, stuff) != 0) {
		if (getType(stuff) != "") {
		    if( type_codes[getType(stuff)] == "" ){
		    	return getType(stuff);
		    }
		}
	}
}

function checkForUnknownLanguage(entry,stuff) {
	if (parseLiteral(entry, stuff) != 0) {
		if (getLanguage(stuff) != "") {
			if( type_codes[getLanguage(stuff)] == "" ){
		    	return getLanguage(stuff);
		    }
		}
	}
}
