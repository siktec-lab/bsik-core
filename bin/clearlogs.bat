@echo off

::%cd% refers to the current working directory (variable)
::%~dp0 refers to the full path to the batch file's directory (static)
::%~dpnx0 and %~f0 both refer to the full path to the batch directory and file name (static).

SET LOGS=%~dp0/../logs/

echo.
set /P confirm=This wil clear all logs - confirm this operation [Y,N]: 
if %confirm%== Y goto yes
if %confirm%== y goto yes
if %confirm%== yes goto yes
if %confirm%== YES goto yes

:termin
echo.
echo Canceling log clear procedure.
goto :EOF

:yes
echo.
call :SL "Clearing log directory...." 
rmdir /s /q "%LOGS%"
mkdir "%LOGS%"
echo DONE.
goto :EOF

:SL (sameline)
echo|set /p=%1
exit /b