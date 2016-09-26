#!/bin/bash
#
#  Script to build load files from a NTriples or NQuads file.  Following any 
# optional arguments, The first argument must always be the file type, which
# is either "nt" for NTriples or "quad" for NQuads.  Then there are two modes
# of operation:
#
# 1) Given a single input triples or quads file, the scripts sorts that file
#    twice to create the two sorted files needed for generating edge sets.
#    In this case, extra options to speed up may be given after --sort-options
# 2) Since sorting can be very expensive, the script can also take the two
#    sorted triples or quads files as input.  In this case, the first file
#    must be sorted by object, and the second by object.
#
DIR=`dirname "$0"`
LONG_FRAC=0
PARALLEL=1
PER_SORT_PARALLEL=1
GRANULARITY=100000
HASHES=3
DROP=0
INVOKED_FROM_WRAPPER=0
IS_CYGWIN=0

case "`uname`" in
	CYGWIN*) IS_CYGWIN=1;
esac

ClassPath=
if [[ $IS_CYGWIN == 1 ]]; then
	ClassPath=`cygpath --path --unix "$CLASSPATH"`
else
	ClassPath="$CLASSPATH"
fi

while [[ "--" = `expr substr $1 1 2` ]]; do
    if [[ $1 == "--long-string-fraction" ]]; then
	shift
	LONG_FRAC=$1
	shift
    elif [[ $1 == "--sort-options" ]]; then
	shift
	SORT_OPTIONS=$1
	shift
    elif [[ $1 == "--db2-config" ]]; then
	shift
	DB2_CONFIG=$1
	shift
    elif [[ $1 == "--knowledge-base" ]]; then
	shift
	KNOWLEDGE_BASE=$1
	shift
    elif [[ $1 == "--object-names" ]]; then
	shift
	OBJECT_NAMES=$1
	shift
    elif [[ $1 == "--workload-query-dir" ]]; then
	shift
	WORKLOAD_DIR=$1
	shift
    elif [[ $1 == "--system-predicates" ]]; then
	shift
	SYSTEM_PREDICATES=$1
	shift
    elif [[ $1 == "--parallel" ]]; then
	shift
	PARALLEL=$1
	shift
    elif [[ $1 == "--granularity" ]]; then
	shift
	GRANULARITY=$1
	shift
    elif [[ $1 == "--hashes" ]]; then
	shift
	HASHES=$1
	shift
    elif [[ $1 == "--drop" ]]; then
	DROP=1
	shift
    elif [[ $1 == "--entity-in-secondary" ]]; then
	ENTITY_IN_SECONDARY=1
	shift
    elif [[ $1 == "--property-in-secondary" ]]; then
	PROPERTY_IN_SECONDARY=1
	shift
    elif [[ $1 == "--no-lids" ]]; then
	NO_LIDS=1
	shift
    elif [[ $1 == "--from-wrapper" ]]; then
        shift
        INVOKED_FROM_WRAPPER=$1
        shift
    else
	echo "unexpected option $1"
	exit -1
    fi
done

if [[ $INVOKED_FROM_WRAPPER != 1 ]]; then
    export CLASSPATH=$DIR/../bin:$DIR/../../com.ibm.wala.util/bin:$DIR/../lib/wala.jar:$DIR/../lib/hash.jar:$DIR/../lib/antlr-3.3-complete.jar:$DIR/../lib/jena-2.6.3-patched.jar:$DIR/../lib/slf4j-api-1.5.8.jar:$DIR/../lib/slf4j-simple-1.5.8.jar:$DIR/../lib/xercesImpl-2.7.1.jar:$DIR/../lib/arq-2.8.5-patched.jar:$DIR/../lib/iri-0.8.jar:$DIR/../lib/icu4j-3.4.4.jar:$DIR/../lib/commons-logging-1-0-3.jar:$DIR/../lib/db2jcc4.jar:$DIR/../lib/pdq.jar
fi

FILE_TYPE=$1
shift

if [[ $# == 2 ]]; then
    SORTED_SUBJ_NT_FILE=$1
    SORTED_OBJ_NT_FILE=$2
    LOAD_DIR=`dirname "$SORTED_SUBJ_NT_FILE"`

else

	NT_FILE=$1
	LOAD_DIR=`dirname "$NT_FILE"`
       NT_FILE_1=$NT_FILE

	if [[ $IS_CYGWIN == 1 ]]; then
		NT_FILE_CYG=`cygpath --path --unix "$1"`
		LOAD_DIR_CYG=`dirname "$NT_FILE_CYG"`
		NT_FILE_1="$NT_FILE_CYG"
	fi
	    
	java -cp "$CLASSPATH" com.ibm.rdf.store.internal.service.types.TypeMap > $NT_FILE_1.types
    
    if [[ $LONG_FRAC > 0 ]]; then
		sh $DIR/stringLengthDistribution.sh $FILE_TYPE $NT_FILE > $NT_FILE.string_dist
		LONG_LENGTH=`gawk -O -v frac=$LONG_FRAC 'BEGIN { done=0; max=0; } $4>frac && done==0 { print $1; done=1; } { max=$1; } END { if (done==0) { print max; } }' $NT_FILE.string_dist`
    else
		LONG_LENGTH=118
    fi

    SORTED_SUBJ_NT_FILE=${NT_FILE}.sorted_subj
    SORTED_OBJ_NT_FILE=${NT_FILE}.sorted_obj
	
	if [[ $IS_CYGWIN == 1 ]]; then
		SORTED_SUBJ_NT_FILE_1=`cygpath --path --unix "$SORTED_SUBJ_NT_FILE"`
		SORTED_OBJ_NT_FILE_1=`cygpath --path --unix "$SORTED_OBJ_NT_FILE"`
	else
		SORTED_SUBJ_NT_FILE_1="$SORTED_SUBJ_NT_FILE"
		SORTED_OBJ_NT_FILE_1="$SORTED_OBJ_NT_FILE"
	fi
	    

    if [[ $PARALLEL != 1 ]]; then
		PER_SORT_PARALLEL=`expr $PARALLEL / 2`
		if expr $PER_SORT_PARALLEL '>' 1 > /dev/null; then
			SORT_OPTIONS="$SORT_OPTIONS --parallel $PER_SORT_PARALLEL"
			(sort $SORT_OPTIONS "$NT_FILE" | gawk -O -v resultName="$SORTED_SUBJ_NT_FILE_1" -v degree=$PER_SORT_PARALLEL -v fileType=$FILE_TYPE -v splitBy=subject -v granularity=$GRANULARITY -f "$DIR"/strings.awk -f "$DIR"/parse.awk -f "$DIR"/splitTriplesByEntity.awk) &
			(sort $SORT_OPTIONS -k 3 "$NT_FILE" | gawk -O -v resultName="$SORTED_OBJ_NT_FILE_1" -v degree=$PER_SORT_PARALLEL -v fileType=$FILE_TYPE -v splitBy=object -v granularity=$GRANULARITY -f "$DIR"/strings.awk -f "$DIR"/parse.awk -f "$DIR"/splitTriplesByEntity.awk) &
        else
			sort $SORT_OPTIONS "$NT_FILE" > "$SORTED_SUBJ_NT_FILE" &
			sort $SORT_OPTIONS -k 3 "$NT_FILE" > "$SORTED_OBJ_NT_FILE" &
		fi
		wait
    else
		sort $SORT_OPTIONS "$NT_FILE" > "$SORTED_SUBJ_NT_FILE"
		sort $SORT_OPTIONS -k 3 "$NT_FILE" > "$SORTED_OBJ_NT_FILE"
    fi
fi

function process() {
    nt_file=$2

    if [[ $1 == "subject" ]]; then
		direct="true"
		table_name="direct"
    else
		direct="false"
		table_name="reverse"
    fi

	if [[ $IS_CYGWIN == 1 ]]; then
	
		nt_file_cygw=`cygpath --path --unix "$nt_file"`
		
		GAWK_OPTS="-v cutoff=$LONG_LENGTH  -v classPath="$ClassPath" -v typeCodeFile="$NT_FILE_1.types" -v setsFor=$1 -v fileType=$FILE_TYPE -v hashFile=$nt_file_cygw.edge_sets1_${HASHES}.hashes"
		
		nt_file_1="$nt_file_cygw"
	else
		GAWK_OPTS="-v cutoff=$LONG_LENGTH -v classPath="$ClassPath" -v typeCodeFile="$NT_FILE_1.types" -v setsFor=$1 -v fileType=$FILE_TYPE -v hashFile=$nt_file.edge_sets1_${HASHES}.hashes"
		
		nt_file_1=$nt_file
	fi
	
    if expr $PER_SORT_PARALLEL '>' 1 > /dev/null; then
		pids=""
		for f in "$nt_file_1".*[0-9]; do
			gawk -O -v setsFor=$1 -v fileType=$FILE_TYPE -v typeCodeFile="$NT_FILE_1.types" -v typeTable="$f.datatypes" -v localeTable="$f.languages" -f "$DIR"/strings.awk -f "$DIR"/parse.awk -f "$DIR"/types.awk -f "$DIR"/findAllPredOccurrences.awk "$f" > "$f.edge_sets" &
			pids="$pids $!"
		done

		wait $pids

		gawk -O '{ counts[$3] += $2; sizes[$3] = $1; } END { for (key in counts) { print sizes[key] " " counts[key] " " key; } }' "$nt_file_1".*.edge_sets | sort -k1,1n -k2,2nr > "$nt_file_1.edge_sets"
    
    else
	    gawk -O -v setsFor=$1 -v fileType=$FILE_TYPE -v typeCodeFile="$NT_FILE_1.types" -v typeTable="$nt_file_1.datatypes" -v localeTable="$nt_file_1.languages" -f "$DIR"/strings.awk -f "$DIR"/parse.awk  -f "$DIR"/types.awk -f "$DIR"/findAllPredOccurrences.awk "$nt_file_1" | sort -k1,1n -k2,2nr > "$nt_file_1.edge_sets"
    fi

}

function updateDataTypes() {

   if [[ $IS_CYGWIN == 1 ]]; then
	NT_FILE_1="$NT_FILE_CYG"
	LOAD_DIR_1="$LOAD_DIR_CYG"
   else
	NT_FILE_1="$NT_FILE"
	LOAD_DIR_1="$LOAD_DIR"
   fi

    # assign ids to datatypes and languages
    if ls "$NT_FILE_1".*.datatypes > /dev/null 2>&1; then
            sort $SORT_OPTIONS --parallel $PARALLEL --merge --unique "$NT_FILE_1".*.datatypes > "$NT_FILE_1".datatypes
    		rm -f "$LOAD_DIR_1"/datatypes.load
    		gawk 'BEGIN{ DT_SEQ=1501} {printf("%s%s%s%s",DT_SEQ++,"\t",$0,"\n")}' "$NT_FILE_1".datatypes > "$LOAD_DIR_1"/datatypes.load
		gawk -F"\t" '{printf("%s%s%s%s",$2," ",$1,"\n")}' "$LOAD_DIR_1"/datatypes.load >> "$NT_FILE".types
    fi

    if ls "$NT_FILE_1".*.languages > /dev/null 2>&1; then
		sort $SORT_OPTIONS --parallel $PARALLEL --merge --unique "$NT_FILE_1".*.languages > "$NT_FILE_1".languages
    		rm -f "$LOAD_DIR_1"/languages.load
    		gawk 'BEGIN{ LANG_SEQ=10600} {printf("%s%s%s%s",LANG_SEQ++,"\t",$0,"\n")}' "$NT_FILE_1".languages > "$LOAD_DIR_1"/languages.load
    		gawk -F"\t" '{printf("%s%s%s%s",$2," ",$1,"\n")}' "$LOAD_DIR_1"/languages.load >> "$NT_FILE".types
    fi


}

function createLoadFiles() {

    nt_file="$2"

    if [[ $1 == "subject" ]]; then
		direct="true"
		table_name="direct"
    else
		direct="false"
		table_name="reverse"
    fi

    if [[ $IS_CYGWIN == 1 ]]; then
	
		nt_file_cygw=`cygpath --path --unix "$nt_file"`
		
		GAWK_OPTS="-v cutoff=$LONG_LENGTH -v setsFor=$1 -v fileType=$FILE_TYPE"
		
		nt_file_1="$nt_file_cygw"
	else
		GAWK_OPTS="-v cutoff=$LONG_LENGTH -v setsFor=$1 -v fileType=$FILE_TYPE"
		
		nt_file_1="$nt_file"
	fi


	
    if [[ -d $WORKLOAD_DIR ]]; then
		gawk -O -f $DIR/conditional-probabilities.awk "$nt_file_1.edge_sets" > "$nt_file_1.correlations"

		java -cp "$CLASSPATH" com.ibm.rdf.store.internal.hashing.FindWorkloadProxies "$WORKLOAD_DIR" "$nt_file.correlations" > "$nt_file.proxies"
		gawk -O '{ print $2; }' $nt_file_1.proxies | sort | uniq > $nt_file_1.predicates_to_index

		if [[ -f $SYSTEM_PREDICATES ]]; then

			cat $SYSTEM_PREDICATES $nt_file_1.predicates_to_index > $nt_file_1.priority_predicates

			java -cp "$CLASSPATH" com.ibm.rdf.store.internal.hashing.AssignHashesToPredicates "$nt_file.edge_sets" $direct "$nt_file.priority_predicates"
	
		else
	    
			java -cp "$CLASSPATH" com.ibm.rdf.store.internal.hashing.AssignHashesToPredicates "$nt_file.edge_sets" $direct "$nt_file.predicates_to_index"
	    
		fi

    else

		if [[ -f $SYSTEM_PREDICATES ]]; then

			java -cp "$CLASSPATH" com.ibm.rdf.store.internal.hashing.AssignHashesToPredicates "$nt_file.edge_sets" $direct $SYSTEM_PREDICATES
	    
		else
	    
			java -cp "$CLASSPATH" com.ibm.rdf.store.internal.hashing.AssignHashesToPredicates "$nt_file.edge_sets" $direct
		fi
	
    fi    
    
	
	
    if [[ $NO_LIDS == 1 ]]; then
		GAWK_OPTS="$GAWK_OPTS -v dontUseLids=yes"
    fi
    if [[ $ENTITY_IN_SECONDARY == 1 ]]; then
		GAWK_OPTS="$GAWK_OPTS -v useEntityInSecondary=yes"
    fi
    if [[ $PROPERTY_IN_SECONDARY == 1 ]]; then
		GAWK_OPTS="$GAWK_OPTS -v usePropertyInSecondary=yes"
    fi

    if expr $PER_SORT_PARALLEL '>' 1 > /dev/null; then
		part=0
		pids=""
		for f in "$nt_file_1".*[0-9]; do
			gawk -O $GAWK_OPTS -v classPath="$ClassPath" -v typeCodeFile="$NT_FILE_1.types" -v hashFile="$nt_file_1.edge_sets1_${HASHES}.hashes" -v part=$part -v longStringFile="$f.long_strings"  -v primaryFile="$f.primary.load" -v secondaryFile="$f.secondary.load" -v discardFile="$f.discard.nq" -f "$DIR"/strings.awk -f "$DIR"/parse.awk -f "$DIR"/types.awk -f "$DIR"/long-strings.awk -f "$DIR"/createLoadFile.awk "$f" &
			pids="$pids $!"
			part=`expr $part + 1`
		done

		wait $pids

    else 
		
		gawk -O $GAWK_OPTS -v classPath="$ClassPath" -v typeCodeFile="$NT_FILE_1.types" -v hashFile="$nt_file_1.edge_sets1_${HASHES}.hashes" -v part=0 -v longStringFile="$nt_file_1.long_strings" -v primaryFile="$nt_file_1.primary.load" -v secondaryFile="$nt_file_1.secondary.load" -v discardFile="$nt_file_1.discard.nq" -f "$DIR"/strings.awk -f "$DIR"/parse.awk -f "$DIR"/types.awk -f "$DIR"/long-strings.awk -f "$DIR"/createLoadFile.awk "$nt_file_1" 
		
		mv -f "$nt_file.primary.load" "$LOAD_DIR/${table_name}-primary.load" 
		if [[ -e "$nt_file.secondary.load" ]]; then
			mv -f "$nt_file.secondary.load" "$LOAD_DIR/${table_name}-secondary.load" 
		fi
    fi
}

if [[ $PARALLEL != 1 ]]; then
# direct tables
    (process "subject" "$SORTED_SUBJ_NT_FILE") &

# reverse tables
    (process "object" "$SORTED_OBJ_NT_FILE") &

    wait

    updateDataTypes

# direct tables
    (createLoadFiles "subject" "$SORTED_SUBJ_NT_FILE") &

# reverse tables
    (createLoadFiles "object" "$SORTED_OBJ_NT_FILE") &

    wait
     
else
# direct tables
    process "subject" "$SORTED_SUBJ_NT_FILE"

# reverse tables
    process "object" "$SORTED_OBJ_NT_FILE"
    
    updateDataTypes

# direct tables
    createLoadFiles "subject" "$SORTED_SUBJ_NT_FILE"
    
# reverse tables
    createLoadFiles "object" "$SORTED_OBJ_NT_FILE"


    
fi

# single long strings table
if [[ $IS_CYGWIN == 1 ]]; then
	NT_FILE_1="$NT_FILE_CYG"
	LOAD_DIR_1="$LOAD_DIR_CYG"
else
	NT_FILE_1="$NT_FILE"
	LOAD_DIR_1="$LOAD_DIR"
fi

if ls "$NT_FILE_1".*.long_strings > /dev/null 2>&1; then
    sort $SORT_OPTIONS --parallel $PARALLEL --merge --unique "$NT_FILE_1".*.long_strings > "$NT_FILE_1".long_strings
    rm -f "$LOAD_DIR_1"/long-strings.load
    mv -f "$NT_FILE_1".long_strings "$LOAD_DIR_1"/long-strings.load
fi

if ls "$NT_FILE_1".*.discard.nq > /dev/null 2>&1; then
    sort $SORT_OPTIONS --parallel $PARALLEL --merge --unique "$NT_FILE_1".*.discard.nq > "$NT_FILE_1".discard.nq
fi

# assemble predicate mapping data
cat "$SORTED_SUBJ_NT_FILE".edge_sets1_${HASHES}.load "$SORTED_OBJ_NT_FILE".edge_sets1_${HASHES}.load | sort > "$LOAD_DIR"/predicate_mappings.load

# create DB2 command file, if given db2 config
if [[ -f "$DB2_CONFIG" ]]; then
	if [[ $IS_CYGWIN == 1 ]]; then
		DB2_CONFIG_CYG=`cygpath --path --unix "$DB2_CONFIG"`
		. "$DB2_CONFIG_CYG"
	else
		. "$DB2_CONFIG"
	fi
    
    DPH_TABLE=${KNOWLEDGE_BASE}_DPH
    DS_TABLE=${KNOWLEDGE_BASE}_DS
    RPH_TABLE=${KNOWLEDGE_BASE}_RPH
    RS_TABLE=${KNOWLEDGE_BASE}_RS
    LSTR_TABLE=${KNOWLEDGE_BASE}_LSTR
    DT_TABLE=${KNOWLEDGE_BASE}_DT

    if [[ -f "$OBJECT_NAMES" ]]; then
	if [[ $IS_CYGWIN == 1 ]]; then
		OBJECT_NAMES_CYG=`cygpath --path --unix "$OBJECT_NAMES"`
		. "$OBJECT_NAMES_CYG"
	else
		. "$OBJECT_NAMES"
	fi
  
	DPH_TABLE=${direct_primary_hash}   	
	DS_TABLE=${direct_secondary}
    	RPH_TABLE=${reverse_primary_hash}
    	RS_TABLE=${reverse_secondary}
    	LSTR_TABLE=${long_strings}
    	DT_TABLE=${data_type}
	
    fi
    function load_parallel_tables() {
	LAST=`ls -1 "$1"*.$2.load | tail -n 1`
	OIFS="$IFS"
	IFS=$'\n' 
	for f in `ls -1 "$1"*.$2.load`; do
	    if [[ $2 == "primary" ]]; then
	    	if [[ "$f" = "$LAST" ]]; then
			MODE="REBUILD"
	   	 else
			MODE="DEFERRED"
	    	fi
	    else
		 MODE="INCREMENTAL"
           fi
		if [[ $IS_CYGWIN == 1 ]]; then
			loadFile=`cygpath --path --windows "$f"`
		else
			loadFile="$f"
		fi
	    cat >> "$NT_FILE".db2_cmds <<EOF
LOAD FROM "$loadFile" OF DEL MODIFIED BY fastparse keepblanks coldel0x09 INSERT INTO $3 NONRECOVERABLE INDEXING MODE $MODE
EOF
	done
	IFS="$OIFS"
    }
    
    cat > "$NT_FILE".db2_cmds <<EOF
CONNECT TO $DB2_DB
EOF

    REORG=0
    if [[ $ENTITY_IN_SECONDARY == 1 ]];  then
		REORG=1
		cat >> "$NT_FILE".db2_cmds <<EOF
alter table ${DS_TABLE} add column ENTITY VARCHAR(118)
alter table ${RS_TABLE} add column ENTITY VARCHAR(118)
EOF
    fi

    if [[ $PROPERTY_IN_SECONDARY == 1 ]];  then
		REORG=1
		cat >> "$NT_FILE".db2_cmds <<EOF
alter table ${DS_TABLE} add column PROP VARCHAR(118)
alter table ${RS_TABLE} add column PROP VARCHAR(118)
EOF
    fi

    if [[ $NO_LIDS == 1 ]];  then
		REORG=1
		cat >> "$NT_FILE".db2_cmds <<EOF
alter table ${DS_TABLE} drop column LIST_ID cascade
alter table ${RS_TABLE} drop column LIST_ID cascade
EOF
    fi

    if [[ $REORG == 1 ]]; then
		cat >> "$NT_FILE".db2_cmds <<EOF
reorg table ${DS_TABLE}
reorg table ${RS_TABLE}
EOF
    fi

    if expr $PER_SORT_PARALLEL '>' 1 > /dev/null; then
	
		load_parallel_tables "$SORTED_SUBJ_NT_FILE_1" "primary" ${DPH_TABLE}
		load_parallel_tables "$SORTED_SUBJ_NT_FILE_1" "secondary" ${DS_TABLE}
		load_parallel_tables "$SORTED_OBJ_NT_FILE_1" "primary" ${RPH_TABLE}
		load_parallel_tables "$SORTED_OBJ_NT_FILE_1" "secondary" ${RS_TABLE}
	
    else
		
		
if [[ -e "$LOAD_DIR"/direct-primary.load ]]; then
cat >> "$NT_FILE".db2_cmds <<EOF
LOAD FROM "$LOAD_DIR/direct-primary.load" OF DEL MODIFIED BY fastparse keepblanks coldel0x09 INSERT INTO ${DPH_TABLE} NONRECOVERABLE INDEXING MODE REBUILD
EOF
fi

if [[ -e "$LOAD_DIR"/reverse-primary.load ]]; then
cat >> "$NT_FILE".db2_cmds <<EOF
LOAD FROM "$LOAD_DIR/reverse-primary.load" OF DEL MODIFIED BY fastparse keepblanks coldel0x09 INSERT INTO ${RPH_TABLE} NONRECOVERABLE INDEXING MODE REBUILD
EOF
fi

if [[ -e "$LOAD_DIR"/direct-secondary.load ]]; then
cat >> "$NT_FILE".db2_cmds <<EOF
LOAD FROM "$LOAD_DIR/direct-secondary.load" OF DEL MODIFIED BY fastparse keepblanks coldel0x09 INSERT INTO ${DS_TABLE} NONRECOVERABLE INDEXING MODE REBUILD
EOF
fi

if [[ -e "$LOAD_DIR"/reverse-secondary.load ]]; then
cat >> "$NT_FILE".db2_cmds <<EOF
LOAD FROM "$LOAD_DIR/reverse-secondary.load" OF DEL MODIFIED BY fastparse keepblanks coldel0x09 INSERT INTO ${RS_TABLE} NONRECOVERABLE INDEXING MODE REBUILD
EOF
fi


    fi

    if [[ -e "$LOAD_DIR"/long-strings.load ]]; then
		cat >> "$NT_FILE".db2_cmds <<EOF
LOAD FROM "$LOAD_DIR/long-strings.load" OF DEL MODIFIED BY fastparse keepblanks coldel0x09 INSERT INTO ${LSTR_TABLE} NONRECOVERABLE INDEXING MODE REBUILD
UPDATE ${KNOWLEDGE_BASE} set haslongstrings = 1
EOF
    fi
    
    if [[ -e "$LOAD_DIR"/datatypes.load ]]; then
	cat >> "$NT_FILE".db2_cmds <<EOF
LOAD FROM "$LOAD_DIR/datatypes.load" OF DEL MODIFIED BY fastparse keepblanks coldel0x09 INSERT INTO ${DT_TABLE} NONRECOVERABLE INDEXING MODE REBUILD
EOF
    fi
    
     if [[ -e "$LOAD_DIR"/languages.load ]]; then
	cat >> "$NT_FILE".db2_cmds <<EOF
LOAD FROM "$LOAD_DIR/languages.load" OF DEL MODIFIED BY fastparse keepblanks coldel0x09 INSERT INTO ${DT_TABLE} NONRECOVERABLE INDEXING MODE REBUILD
EOF
    fi

    cat >> "$NT_FILE".db2_cmds <<EOF
COMMIT WORK
EOF

    if [[ $INVOKED_FROM_WRAPPER != 1 ]]; then
    # create the store, and load data if possible (must be on db2 machine)
        OPTS="-db $DB2_DB -host $DB2_HOST -port $DB2_PORT -user $DB2_USER -password $DB2_PASSWORD -schema $DB2_SCHEMA"
    
        CREATE_OPTS="-predicateMappings $LOAD_DIR/predicate_mappings.load $OPTS"
    
        if [[ -f $SYSTEM_PREDICATES ]]; then
	    CREATE_OPTS="$CREATE_OPTS -systempredicates $SYSTEM_PREDICATES"
        fi
    
        if [[ $DROP == 1 ]]; then
	    java -cp $CLASSPATH com.ibm.rdf.store.cmd.DropRdfStore $KNOWLEDGE_BASE $OPTS
        fi
    
        java -Dcom.ibm.rdf.createStore="true" -Dcom.ibm.rdf.createIndex="false" -cp $CLASSPATH com.ibm.rdf.store.cmd.CreateRdfStore $KNOWLEDGE_BASE $CREATE_OPTS 
    
        if which db2 > /dev/null 2>&1; then
	    db2 -c- < "$NT_FILE".db2_cmds
        fi
    
        java -Dcom.ibm.rdf.createStore="false" -Dcom.ibm.rdf.createIndex="true" -Dcom.ibm.rdf.store.no_lids=$NO_LIDS -cp $CLASSPATH com.ibm.rdf.store.cmd.CreateRdfStore $KNOWLEDGE_BASE $CREATE_OPTS
    
        if which db2 > /dev/null 2>&1; then
	    java -cp $CLASSPATH com.ibm.rdf.store.cmd.UpdateRdfStoreStats $KNOWLEDGE_BASE $OPTS
        fi
    
    fi
fi
