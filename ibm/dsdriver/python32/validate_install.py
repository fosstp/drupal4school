import sys
import ibm_db
if len(sys.argv) < 2:
    print "Database Name not specified. Specify the DatabaseName and credentials or the DSN alias to which the program needs to connect\n"
    sys.exit(1)

databaseName = sys.argv[1]
conn_string = ""
uNameArg    = ""
pwdArg      = ""

if len(sys.argv) == 6:
    hostName = sys.argv[2]
    port = sys.argv[3]
    userName = sys.argv[4]
    password = sys.argv[5]
    
    conn_string = "DRIVER={IBM DB2 ODBC DRIVER};DATABASE=%s;HOSTNAME=%s;PORT=%s;PROTOCOL=TCPIP;UID=%s;PWD=%s;" % (databaseName, hostName, port, userName, password)
elif len(sys.argv) == 4:
    conn_string = databaseName
    uNameArg    = sys.argv[2]
    pwdArg      = sys.argv[3]
elif len(sys.argv) == 2:
    conn_string = databaseName
else:
    print "Wrong number of arguments. Specify the DatabaseName and credentials or the DSN alias to which the program needs to connect\n"
    sys.exit(1)

try:    
    conn = ibm_db.connect(conn_string, uNameArg, pwdArg)
    print "Connection to database %s is successful\n" % (databaseName, )
    ibm_db.close(conn)
except Exception, inst:
    print "Connection to database %s failed with message: %s\n" % (databaseName, inst)
