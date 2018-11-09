@echo off
echo Chemins php et du script a lancer

SET path_php="C:\wamp64\bin\php\php7.0.10"
SET plugin_glpi="C:\wamp\www\glpi\plugins\ocsinventoryng\scripts"

echo Definition du path

PATH = %path_php%;%PATH%


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