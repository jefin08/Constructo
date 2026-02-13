@echo off
setlocal
set PHP_BIN=php
if exist "%~dp0php\php.exe" set PHP_BIN="%~dp0php\php.exe"
if exist "%~dp0php\php\php.exe" set PHP_BIN="%~dp0php\php\php.exe"

echo Starting PHP Server using: %PHP_BIN%
echo Access the application at http://localhost:8000
%PHP_BIN% -S localhost:8000
pause
