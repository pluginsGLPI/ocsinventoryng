@echo off
echo Chemins php et du script a lancer

SET path_php="C:\wamp\bin\php\php8.3.24"
SET plugin_glpi="C:\wamp\www\glpi11\marketplace\ocsinventoryng\public\scripts"

echo Definition du path

PATH = %PATH%;%path_php%


IF EXIST %plugin_glpi%\run.php GOTO RUN

IF NOT EXIST %plugin_glpi%\run.php GOTO EXIT

:RUN
echo Lancement du script
php %plugin_glpi%\run.php
GOTO FIN

:EXIT
echo Le chemin vers run.php est incorrect
pause

:FIN
