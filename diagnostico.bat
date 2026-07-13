@echo off
REM ===================================================================
REM  Diagnostico do projeto (XAMPP / Windows)
REM  Coloque este ficheiro na RAIZ do projeto e execute-o.
REM ===================================================================
setlocal

REM Ajuste este caminho se o seu PHP nao estiver no XAMPP por omissao
set PHP=C:\xampp\php\php.exe

if not exist "%PHP%" (
  echo [ERRO] Nao encontrei o PHP em %PHP%
  echo Edite este ficheiro e corrija a variavel PHP.
  pause
  exit /b 1
)

echo.
echo === 1. Diagnostico completo ===
"%PHP%" spark app:diagnostico

echo.
echo === 2. Rotas registadas ===
"%PHP%" spark routes

echo.
echo === 3. Estado das migrations ===
"%PHP%" spark migrate:status

echo.
pause
