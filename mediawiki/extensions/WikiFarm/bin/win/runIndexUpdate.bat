@echo off
setlocal

if not exist %~dp0\setup.bat (
    echo Configuration file "setup.bat" does not exist. Please create it.
    exit /B 1
)

rem Usage: strip-prefix.bat env-farm-foo
set "IN=%~1"
if "%IN%"=="" (
  echo Usage: %~nx0 ^<env-farm-dir^>
  exit /b 1
)

set "PREFIX=env-farm-"

rem Remove prefix only if it is at the start
if /i "%IN:~0,9%"=="%PREFIX%" (
  set "OUT=%IN:~9%"
) else (
  set "OUT=%IN%"
)

echo %OUT%
SET WIKI=%OUT%

CALL %~dp0\setup.bat %OUT%

if not errorlevel 1 (
     "!PHP_BIN!" "!MW!\extensions\FacetedSearch2\maintenance\updateIndex.php" -v
)

exit /b 0