@echo off
TITLE E-Queue System - Startup Master
echo If you see an error about "running scripts is disabled", 
echo please run: Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser 
echo in an Administrator PowerShell window.
start "Sentiment Analysis Server" powershell -NoExit -ExecutionPolicy Bypass -Command "& { Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process -Force; .\.venv\Scripts\Activate.ps1; cd sentiment_analysis; uvicorn app:app --host 127.0.0.1 --port 8000 }"
timeout /t 3 /nobreak > nul
start http://localhost/ISPSC-E-QUEUE/public/index.php
pause
