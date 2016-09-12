BEGIN {
    defaultType = "<http://untyped.literal>";
}

function trimString(str) {
    return gensub(/^[[:space:]]*/, "", "", gensub(/[[:space:]]*$/, "", "", str));
}

function fixBrackets(str) {
    if (index(str, "<") == 1) {
	return gensub(/^<([^>]*)+>$/, "\\1", "", str);
    } else {
	return str;
    }
}

function parseLiteral(literal, result) {
    return match(literal, /^"(.*)"(\^\^<([^"]*)>|\@([^"]*))?$/, result);
}

function getString(a) {
    return a[1];
}

function getType(a) {
    return a[3];
}

function getLanguage(a) {
    return a[4];
}

function getTag(a) {
    if (getLanguage(a) != "") {
	return getLanguage(a);
    } else if (getType(a) != "") {
	return getType(a);
    } else {
	return defaultType;
    }
}

function escapeDoubleQuotes(str){
    return gensub(/\\\"/,"\"\"","G",str);
}

