#!/bin/sh
#Copyright(external)
# Licensed Materials - Property of IBM
# (c) Copyright IBM Corp. 2013.   All Rights Reserved.

# US Government Users Restricted Rights
# Use, duplication or disclosure restricted by GSA ADP Schedule
# Contract with IBM Corporation.

# IBM grants you ("Licensee") a non-exclusive, royalty free, license to use,
# copy and redistribute the Non-Sample Header file software in source and binary code form,
# provided that i) this copyright notice, license and disclaimer  appear on all copies of
# the software: and ii) Licensee does not utilize the software in a manner
# which is disparaging to IBM.


# This software is provided "AS IS."  IBM and its Suppliers and Licensors expressly disclaim all warranties, whether  EXPRESS OR IMPLIED,
# INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR WARRANTY OF  NON-INFRINGEMENT.  IBM AND ITS SUPPLIERS AND  LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE THAT RESULT FROM USE OR DISTRIBUTION OF THE SOFTWARE OR THE COMBINATION OF THE SOFTWARE WITH ANY OTHER CODE.  IN NO EVENT WILL IBM OR ITS SUPPLIERS  AND LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE SOFTWARE, EVEN IF IBM HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

# @endCopyright

NOSQLUSER=
NOSQLHOSTNAME=localhost
NOSQLDB=
NOSQLPORT=50000
NOSQLPASSWORD=
NOSQLSETUP=
NOSQLURL=

echo JSON Command Shell Setup and Launcher.
echo This batch script assumes your JRE is 1.5 and higher. 1.6 will mask your password.
echo Type db2nosql.sh -help to see options

while [ $# -gt 0 ]
do
  #echo $1
  #echo $2
   
  case $1 in

  # Normal option processing
    -help)
      # usage and help
      #print some stuff and end return to os prompt 
      echo "db2nosql.sh is used with the following options:"
      echo "-help will print out help"
      echo "-user username , if not provided, we will use a Type 2 JDBC connection (uses OS username/password)"
      echo "-hostname host URL, if not provided, default is localhost"
      echo "-port port for db, if not provided, default is 50000"
      echo "-db database Name, if not provided,  command line will prompt"
      echo "-password password for db and user, if not provided, command line will prompt"
      echo "-setup enable/disable/migrate, enable creates artifacts, disable removes them, migrate migrates artifacts from previous releases (against DB2 LUW only)"
      echo "-url \"<T4 JDBC URL>\", full jdbc url with options. ENCLOSE URL IN QUOTES. If using -url, -db, -port, and -hostname will be ignored"
      echo "Example: db2nosql.sh -user bob -hostName bob.bobhome.com -port 23023 -db bobdb -setup enable -password mypassword"
      echo "Example: db2nosql.bat -user bob -url \"jdbc:db2://bob.bobhome.com:50000/mydb:traceLevel=ALL;traceFile=C:/jcctrace.txt;\" -setup enable -password mypassword"
  
      exit 0
      ;;
    -user)
      # user name
       if ! [ -z "$NOSQLUSER" ]
       then 
        echo "Please provide only one -user"
        exit 0
       fi 
       
       if [ ! -z "$2" ]
       then NOSQLUSER=$2
       fi
      ;;
    -hostname | -hostName)
      # host name
       if ! [ "$NOSQLHOSTNAME" = "localhost" ]
       then 
        echo "Please provide only one -hostname"
        exit 0
       fi
       
       if [ ! -z "$2" ] 
       then NOSQLHOSTNAME=$2
       fi
      ;;
    -db)
      # db name
       if ! [ -z "$NOSQLDB" ]
       then 
        echo "Please provide only one -db"
        exit 0
       fi
       
       if [ ! -z "$2" ] 
       then NOSQLDB=$2
       fi
      ;;
    -port)
      # port  
      
       if ! [ "$NOSQLPORT" = "50000" ]
       then 
        echo "Please provide only one -port"
        exit 0
       fi
       
       if [ ! -z "$2" ] 
       then NOSQLPORT=$2
       fi
      ;;
    -setup)
      # setup value
       if ! [ -z "$NOSQLSETUP" ]
       then 
        echo "Please provide only one -setup"
        exit 0
       fi
        
       if [ ! -z "$2" ] 
       then NOSQLSETUP=$2
       fi
      ;;
    -password)
      # password
      
       if ! [ -z "$NOSQLPASSWORD" ]
       then 
        echo "Please provide only one -password"
        exit 0
       fi
       
      
       if [ ! -z "$2" ] 
       then NOSQLPASSWORD=$2
       fi
      ;;
    -url)
      # url
      
       if ! [ -z "$NOSQLURL" ]
       then 
        echo "Please provide only one -url"
        exit 0
       fi
       
       if [ ! -z "$2" ] 
       then NOSQLURL=$2
       fi
      ;;      
 
   esac

  shift
done


if [ -z "$NOSQLDB" ] && [ -z "$NOSQLURL" ]  
then
echo -n "Enter DB:"
read NOSQLDB
fi


 
if [ -z "$NOSQLDB" ] && [ -z "$NOSQLURL" ] 
then
echo "DB or URL must be provided"
exit 0
fi


if [ "$NOSQLSETUP" = "enable" ]
  then
    if ! [ -z "$NOSQLURL" ]
      then
         if [ -z "$NOSQLPASSWORD" ] || [ -z "$NOSQLUSER" ]
           then
               echo "-user and -password must be provided when -url is used."
               exit 0
           else  
            java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url $NOSQLURL --user $NOSQLUSER --password $NOSQLPASSWORD --enable true
         fi  
    elif [ -z "$NOSQLPASSWORD" ] && [ -z "$NOSQLUSER" ]
       then
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2:$NOSQLDB --enable true
    elif  [ -z "$NOSQLPASSWORD" ]
       then
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2://$NOSQLHOSTNAME:$NOSQLPORT/$NOSQLDB --user $NOSQLUSER --enable true 
    else
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2://$NOSQLHOSTNAME:$NOSQLPORT/$NOSQLDB --user $NOSQLUSER --enable true --password $NOSQLPASSWORD
    fi

elif [ "$NOSQLSETUP" = "disable" ]
 then

   if ! [ -z "$NOSQLURL" ]
      then
         if [ -z "$NOSQLPASSWORD" ] || [ -z "$NOSQLUSER" ]
           then
               echo "-user and -password must be provided when -url is used."
               exit 0
           else  
            java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url $NOSQLURL --user $NOSQLUSER --password $NOSQLPASSWORD --disable true
         fi  
    elif [ -z "$NOSQLPASSWORD" ] && [ -z "$NOSQLUSER" ]
       then
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2:$NOSQLDB --disable true
    elif  [ -z "$NOSQLPASSWORD" ]
       then
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2://$NOSQLHOSTNAME:$NOSQLPORT/$NOSQLDB --user $NOSQLUSER --disable true 
    else
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2://$NOSQLHOSTNAME:$NOSQLPORT/$NOSQLDB --user $NOSQLUSER --disable true --password $NOSQLPASSWORD
    fi

elif [ "$NOSQLSETUP" = "migrate" ]
 then

   if ! [ -z "$NOSQLURL" ]
      then
         if [ -z "$NOSQLPASSWORD" ] || [ -z "$NOSQLUSER" ]
           then
               echo "-user and -password must be provided when -url is used."
               exit 0
           else  
            java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url $NOSQLURL --user $NOSQLUSER --password $NOSQLPASSWORD --migrate true
         fi  
    elif [ -z "$NOSQLPASSWORD" ] && [ -z "$NOSQLUSER" ]
       then
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2:$NOSQLDB --migrate true
    elif  [ -z "$NOSQLPASSWORD" ]
       then
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2://$NOSQLHOSTNAME:$NOSQLPORT/$NOSQLDB --user $NOSQLUSER --migrate true 
    else
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2://$NOSQLHOSTNAME:$NOSQLPORT/$NOSQLDB --user $NOSQLUSER --migrate true --password $NOSQLPASSWORD
    fi
    
else
    
    if ! [ -z "$NOSQLURL" ]
      then
         if [ -z "$NOSQLPASSWORD" ] || [ -z "$NOSQLUSER" ]
           then
               echo "-user and -password must be provided when -url is used."
               exit 0
           else  
            java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url $NOSQLURL --user $NOSQLUSER --password $NOSQLPASSWORD
         fi       
    elif [ -z "$NOSQLPASSWORD" ] && [ -z "$NOSQLUSER" ]
       then
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2:$NOSQLDB
    elif [ -z "$NOSQLPASSWORD" ]
       then
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2://$NOSQLHOSTNAME:$NOSQLPORT/$NOSQLDB --user $NOSQLUSER
    else      
       java -cp "../../tools/jline-0.9.93.jar:../lib/nosqljson.jar:../lib/js.jar:../lib/mongo-2.8.0.jar:$CLASSPATH" com.ibm.nosql.json.cmd.NoSqlCmdLine  --url jdbc:db2://$NOSQLHOSTNAME:$NOSQLPORT/$NOSQLDB --user $NOSQLUSER --password $NOSQLPASSWORD
    fi
fi
