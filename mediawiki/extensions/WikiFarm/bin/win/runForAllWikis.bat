@echo off
setlocal enabledelayedexpansion

rem ===== Configuration =====
rem Root folder to scan (default: folder where this .bat lives)
set "ROOT=%~dp0..\..\..\..\"

rem The script to run for each matching folder
rem (can be .bat, .cmd, .ps1, .exe, etc.)
set "RUN_SCRIPT=%~dp0%1"

rem ===== Validate =====
if not exist "%ROOT%" (
  echo Root folder does not exist: "%ROOT%"
  exit /b 1
)

if not exist "%RUN_SCRIPT%" (
  echo Script to run not found: "%RUN_SCRIPT%"
  exit /b 1
)


rem ===== Iterate matching subdirectories: env-* =====
for /d %%D in ("%ROOT%env-farm-*") do (
  if exist "%%~fD\" (
    echo === Running in: %%~nxD ===
    pushd "%%~fD" || (
      echo Failed to enter directory: "%%~fD"
      exit /b 1
    )

    call "%RUN_SCRIPT%" %%~nxD
    set "RC=!errorlevel!"

    popd

    if not "!RC!"=="0" (
      echo Script failed in %%~nxD with exit code !RC!
      exit /b !RC!
    )
  )
)

echo Done.
exit /b 0