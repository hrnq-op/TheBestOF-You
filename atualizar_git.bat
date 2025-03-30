@echo off
cd /d C:\xampp\htdocs\Projetos\TheBestOF-You
git pull origin master
git add .
set /p mensagem="Digite a mensagem do commit: "
git commit -m "%mensagem%"
git push origin master
echo.
echo Atualização concluída!
pause
