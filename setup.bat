@echo off
setlocal
set PHP_BIN=php
if exist "%~dp0php\php.exe" set PHP_BIN="%~dp0php\php.exe"
if exist "%~dp0php\php\php.exe" set PHP_BIN="%~dp0php\php\php.exe"

echo Using PHP: %PHP_BIN%
%PHP_BIN% setup_database.php
pause
