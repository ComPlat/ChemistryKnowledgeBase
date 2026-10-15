@echo off

rem Create a copy if this file, named setup.bat, which will be used by all other batch files in this folder.

IF ["%~1"] == [""] GOTO help

SET MW=D:\interpub\wikifarm\mediawiki
SET WIKI=%1
SET DB_USER=root
SET DB_PASS=vagrant
SET MYSQL_BIN=C:\Program Files\MariaDB 10.11\bin\mariadb.exe
SET MYSQL_DUMP_BIN=C:\Program Files\MariaDB 10.11\bin\mariadb-dump.exe
SET PHP_BIN=C:\PHP_8_3\php.exe

SET DB_NAME=%1_wikidb

exit /B

:help
echo Wiki-ID is missing. Pass it as an argument, e.g.: setup.bat <wiki-ID>
exit /B 1

