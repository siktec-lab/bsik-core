@echo off

::%cd% refers to the current working directory (variable)
::%~dp0 refers to the full path to the batch file's directory (static)
::%~dpnx0 and %~f0 both refer to the full path to the batch directory and file name (static).

SET STARTPOINT=%cd%
SET CORE=%~dp0/../manage/pages
SET FRONT=%~dp0/../front/
SET MODULES=%~dp0/../manage/modules/


set /a idx=0

setlocal enableDelayedExpansion

FOR /d /r "%CORE%" %%F IN (cache?) DO (
    ::echo deleting folder: %%F
    set /a idx+=1
    set "cachedirs[!idx!]=%%~F"
)

FOR /d /r "%MODULES%" %%F IN (cache?) DO (
    ::echo deleting folder: %%F
    set /a idx+=1
    set "cachedirs[!idx!]=%%~F"
)

FOR /d /r "%FRONT%" %%F IN (cache?) DO (
    ::echo deleting folder: %%F
    set /a idx+=1
    set "cachedirs[!idx!]=%%~F"
)

echo.
echo Found the folowing cache folders:
for /l %%i in (1,1,!idx!) do ( 
   echo     !cachedirs[%%i]!
)

echo.
set /P confirm=Confirm those directories please before I wipe them [Y,N]: 
if %confirm%== Y goto yes
if %confirm%== y goto yes
if %confirm%== yes goto yes
if %confirm%== YES goto yes

:termin

echo.
echo Terminating cache cleaning procedure

goto :EOF

:yes

echo.
echo Clearing cache directories:
for /l %%i in (1,1,!idx!) do ( 
    call :SL "|    - Clearing: !cachedirs[%%i]!..."
    rmdir /s /q "!cachedirs[%%i]!"
    mkdir "!cachedirs[%%i]!"
    echo DONE
)
goto :EOF

:SL (sameline)
echo|set /p=%1
exit /b
