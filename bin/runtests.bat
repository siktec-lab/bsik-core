@echo off
:: --------------------------------------------------------
:: Run tests of BSIK Framework
:: --------------------------------------------------------
SET UNITTESTTARGET=%~dp0/../vendor/bin/phpunit

if "%1"=="" ( 
    goto fulltest 
) else ( 
    goto specific
)

:specific

if exist %~dp0/../tests/%1Test.php (
    echo - Running BSIK Test - %1Test
    php "%UNITTESTTARGET%" --testdox --color="auto" tests/%1Test.php
) else (
    if exist %~dp0/../manage/tests/%1.php (
        echo - Running BSIK Test - %1
        php "%UNITTESTTARGET%" --testdox --color="auto" tests/%1.php
    ) else (
        echo Test not found check your spelling and use CI name.
    )
)
goto :eof

:fulltest

echo - Running BSIK Tests...
php "%UNITTESTTARGET%" --testdox --color="auto" tests
goto :eof