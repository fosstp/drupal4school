#!/bin/bash
set -e
rm -rf /usr/local/apache/logs/httpd.pid

exec /usr/sbin/httpd -DFOREGROUND
