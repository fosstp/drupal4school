<?php
$mssql_conn = NULL;

function mssql_test() {
  global $mssql_conn;
  if (!$mssql_conn) {
    $mssql_host = variable_get('db2health_server');
    $mssql_sa = variable_get('db2health_server_sa');
    $mssql_pass = variable_get('db2health_server_pass');
    $mssql_conn = mssql_connect($mssql_host, $mssql_sa, $mssql_pass);
  }
  if ($mssql_conn) {
    return TRUE;
  }
  else {
    return FALSE;
  }
}

function mssql_operate($sql) {
  global $mssql_conn;
  if (!$mssql_conn) {
    $mssql_host = variable_get('db2health_server');
    $mssql_sa = variable_get('db2health_server_sa');
    $mssql_pass = variable_get('db2health_server_pass');
    $mssql_conn = mssql_pconnect($mssql_host, $mssql_sa, $mssql_pass);
  }
  if ($mssql_conn) {
	mssql_select_db('Health', $mssql_conn);
	$rs = mssql_query($sql, $mssql_conn);
	return $rs;
  }
}
