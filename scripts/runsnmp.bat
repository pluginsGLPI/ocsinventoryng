@echo off
echo Chemins php et du script a lancer

SET path_php="C:\wamp\bin\php\php5.6.16"
SET plugin_glpi="C:\wamp\www\glpi\plugins\ocsinventoryng\scripts"

echo Definition du path

PATH = %PATH%;%path_php%


IF EXIST %plugin_glpi%\runsnmp.php GOTO RUN

IF NOT EXIST %plugin_glpi%\runsnmp.php GOTO EXIT

:RUN
echo Lancement du script
php %plugin_glpi%\runsnmp.php
GOTO FIN

:EXIT
echo Le chemin vers runsnmp.php est incorrect
pause

:FIN