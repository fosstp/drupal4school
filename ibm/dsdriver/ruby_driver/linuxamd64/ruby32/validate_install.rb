require (RUBY_PLATFORM =~ /mswin32/ || RUBY_PLATFORM =~ /mingw32/ ) ? 'mswin32/ibm_db' : 'ibm_db.so'

if(ARGV.length == 0 || ARGV[0].nil?)
  puts "Database Name not specified. Specify the DatabaseName and credentials or the DSN alias to which the program needs to connect"
  exit
end

databaseName = ARGV[0]

conn_string = ""
uNameArg    = ""
pwdArg      = ""

if(ARGV.length == 5)
  hostName = ARGV[1]
  port = ARGV[2]
  userName = ARGV[3]
  password = ARGV[4]

  conn_string = "DRIVER={IBM DB2 ODBC DRIVER};DATABASE=#{databaseName};HOSTNAME=#{hostName};PORT=#{port};PROTOCOL=TCPIP;UID=#{userName};PWD=#{password};"
elsif(ARGV.length == 3)
  conn_string = databaseName
  uNameArg = ARGV[1]
  pwdArg = ARGV[2]
elsif(ARGV.length == 1)
  conn_string = databaseName
else
    puts "Wrong number of arguments. Specify the DatabaseName and credentials or the DSN alias to which the program needs to connect"
    exit
end

begin
   conn = IBM_DB.connect(conn_string,uNameArg,pwdArg)
   puts "Connection to database #{databaseName} is successful"
   IBM_DB.close(conn)
rescue StandardError => err
  puts "Connection to database #{databaseName} failed with message: #{err.message}"
end
