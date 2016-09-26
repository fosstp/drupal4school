#!/bin/sh

# THIS PRODUCT CONTAINS RESTRICTED MATERIALS OF IBM
# 5724-J34, 5655-P28  (C) COPYRIGHT International Business Machines Corp., 2013
# All Rights Reserved * Licensed Materials - Property of IBM
# US Government Users Restricted Rights - Use, duplication or disclosure
# restricted by GSA ADP Schedule Contract with IBM Corp.

ARGS="$@"
var1="$1"

if [ $1 = "-register" ]; then
  shift
  ARGS="$@"
fi

LOGPATH=
NOSQLPROPERTYPATH=
if [ $# -ne 0 ]; then
    while [ $# -gt 0 ]; do
	if [ $1 = "-logPath" ]; then
	    if [ $# -gt 1 ]; then
		LOGPATH=$2
		break
	    fi
	fi
	if [ $1 = "-noSQLPropertyPath" ]; then
	    if [ $# -gt 1 ]; then
		NOSQLPROPERTYPATH="$2":
		break
	    fi
	fi
	shift
    done
fi

binDir=`pwd`
cd ..
JSONDIR=`pwd`

if [ -z "$JAVA_HOME" ]
then
    JAVA_HOME="${JSONDIR}/../java/jdk"
fi

if [ ! -d ${JAVA_HOME} ]; then
    echo "ERROR: ${JAVA_HOME} does not exist. JAVA_HOME is not set correctly."
    cd "$binDir"
    exit 1
fi

if [ -f ${JAVA_HOME}/bin/java ]; then
    JAVA_EXE="${JAVA_HOME}/bin/java"
else
    JAVA_EXE="${JAVA_HOME}/jre/bin/java"
fi

# Setup LOGPATH properly if not exists
if [ -z "${LOGPATH}" ]; then
    LOGPATH=${JSONDIR}/logs
    if [ ! -d ${JSONDIR}/logs ]; then
        mkdir -p ${LOGPATH}
    fi
else
    if [ ! -d "${LOGPATH}" ]; then
        LOGPATH="`dirname \"${LOGPATH}\"`"
    fi
fi

LOGPATH="`(cd \"$LOGPATH\" && pwd)`"
LOGPATHSYS=-Dcom.ibm.ejs.ras.lite.traceFileName=${LOGPATH}/trace.log
# echo LOGPATH is set to ${LOGPATH}
echo $var1

if [ "$var1" == "-register" ]; then
  ${JAVA_EXE} "-classpath" ${NOSQLPROPERTYPATH}"$JSONDIR"/lib/db2NoSQLWireListener.jar:"$JSONDIR/lib/nosqljson.jar" com.ibm.nosql.wireListener.auth.ConfigTool ${ARGS}
else 
  ${JAVA_EXE} ${LOGPATHSYS} -classpath ${NOSQLPROPERTYPATH}"$JSONDIR"/lib/db2NoSQLWireListener.jar:"$JSONDIR"/lib/nosqljson.jar:"$JSONDIR"/lib/js.jar:"$JSONDIR"/../java/db2jcc.jar:"$JSONDIR"/../java/db2jcc4.jar com.ibm.nosql.db2wire.server.DB2Listener ${ARGS}
fi

cd "$binDir"
