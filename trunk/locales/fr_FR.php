<?php
/*
 * @version $Id: HEADER 2011-03-12 18:01:26 tsmr $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
// ----------------------------------------------------------------------
// Original Author of file: CAILLAUD Xavier
// Purpose of file: plugin ocsinventoryng v 1.0.0 - GLPI 0.83
// ----------------------------------------------------------------------
 */

$LANG['plugin_ocsinventoryng']['title'][1] = "Mode OCSNG";

$LANG['plugin_ocsinventoryng'][0]  = "OCS Inventory NG";
$LANG['plugin_ocsinventoryng'][1]  = "Synchronisation manuelle des ordinateurs déjà importés";
$LANG['plugin_ocsinventoryng'][2]  = "Importation manuelle de nouveaux ordinateurs";
$LANG['plugin_ocsinventoryng'][3]  = "Nettoyage des liens GLPI / OCSNG";
$LANG['plugin_ocsinventoryng'][4]  = "Lier des nouveaux ordinateurs à des ordinateurs existants";
$LANG['plugin_ocsinventoryng'][5]  = "Importer nouveaux ordinateurs";
$LANG['plugin_ocsinventoryng'][6]  = "Mise à jour automatique";
$LANG['plugin_ocsinventoryng'][7]  = "Importé depuis OCSNG";
$LANG['plugin_ocsinventoryng'][8]  = "Importation réussie";
$LANG['plugin_ocsinventoryng'][9]  = "Pas de nouvel ordinateur à importer";
$LANG['plugin_ocsinventoryng'][10] = "Ordinateurs mis à jour dans OCSNG";
$LANG['plugin_ocsinventoryng'][11] = "Mise à jour des ordinateurs";
$LANG['plugin_ocsinventoryng'][12] = "Pas de nouvel ordinateur à mettre à jour";
$LANG['plugin_ocsinventoryng'][13] = "Date d'import dans GLPI";
$LANG['plugin_ocsinventoryng'][14] = "Date dernier inventaire OCSNG";
$LANG['plugin_ocsinventoryng'][15] = "Aucun champ verrouillé";
$LANG['plugin_ocsinventoryng'][16] = "Champ(s) verrouillé(s)";
$LANG['plugin_ocsinventoryng'][17] = "Configuration du serveur OCSNG";
$LANG['plugin_ocsinventoryng'][18] = "Connexion à la base de données OCSNG réussie";
$LANG['plugin_ocsinventoryng'][19] = "Version et Configuration OCSNG valide";
$LANG['plugin_ocsinventoryng'][20] = "Version d'OCSNG non valide : nécessite RC3";
$LANG['plugin_ocsinventoryng'][21] = "Échec de connexion à la base de données OCSNG";
$LANG['plugin_ocsinventoryng'][22] = "Attention ! Les données importées (voir votre configuration) écraseront les données existantes";
$LANG['plugin_ocsinventoryng'][23] = "Importation impossible, ordinateur de destination de GLPI déjà lié à un élément d'OCSNG";
$LANG['plugin_ocsinventoryng'][24] = "Forcer la synchronisation";
$LANG['plugin_ocsinventoryng'][25] = "Configuration de la synchronisation automatique";
$LANG['plugin_ocsinventoryng'][26] = "Choix d'un serveur OCSNG";
$LANG['plugin_ocsinventoryng'][27] = "Aucun serveur OCSNG n'est défini";

$LANG['plugin_ocsinventoryng'][29] = "Serveur OCSNG";
$LANG['plugin_ocsinventoryng'][30] = "Moniteur(s) verrouillé(s)";

$LANG['plugin_ocsinventoryng'][32] = "Périphérique(s) verrouillé(s)";

$LANG['plugin_ocsinventoryng'][34] = "Imprimante(s) verrouillée(s)";

$LANG['plugin_ocsinventoryng'][36] = "Entité de destination";
$LANG['plugin_ocsinventoryng'][37] = "Activer la prévisualisation";
$LANG['plugin_ocsinventoryng'][38] = "Désactiver la prévisualisation";
$LANG['plugin_ocsinventoryng'][39] = "Lieu de destination";
$LANG['plugin_ocsinventoryng'][40] = "Vérifie la règle ?";
$LANG['plugin_ocsinventoryng'][41] = "Mode d'import manuel";
$LANG['plugin_ocsinventoryng'][42] = "Configuration OCSNG invalide (TRACE_DELETED doit être activé)";
$LANG['plugin_ocsinventoryng'][43] = "Accès refusé sur la base OCSNG (Droit d'écriture sur hardware.CHECKSUM nécessaire)";
$LANG['plugin_ocsinventoryng'][44] = "Accès refusé sur la base OCSNG (Droit de suppression sur deleted_equiv nécessaire)";
$LANG['plugin_ocsinventoryng'][45] = "ID OCS";
$LANG['plugin_ocsinventoryng'][46] = "Supprimé d'OCSNG";
$LANG['plugin_ocsinventoryng'][47] = "Lié avec un ordinateur d'OCSNG";
$LANG['plugin_ocsinventoryng'][48] = "L'ordinateur a changé d'ID OCSNG";
$LANG['plugin_ocsinventoryng'][49] = "Agent";
$LANG['plugin_ocsinventoryng'][50] = "IP(s) verrouillée(s)";

$LANG['plugin_ocsinventoryng'][52]="Logiciel(s) verrouillé(s)";

$LANG['plugin_ocsinventoryng'][54] = "Logiciel mis dans la corbeille par la synchronisation OCSNG";
$LANG['plugin_ocsinventoryng'][55] = "Volume(s) verrouillé(s)";
$LANG['plugin_ocsinventoryng'][56] = "Composant(s) verrouillé(s)";
$LANG['plugin_ocsinventoryng'][57] = "Interface OCSNG";
$LANG['plugin_ocsinventoryng'][58] = "Liaison OCSNG";
$LANG['plugin_ocsinventoryng'][59] = "Présent dans GLPI";
$LANG['plugin_ocsinventoryng'][60] = "Présent dans OCSNG";
$LANG['plugin_ocsinventoryng'][61] = "Aucun objet à nettoyer.";
$LANG['plugin_ocsinventoryng'][62] = "entité de destination de l'ordinateur";

$LANG['plugin_ocsinventoryng'][67] = "Liaison possible";
$LANG['plugin_ocsinventoryng'][68] = "Import refusé";
$LANG['plugin_ocsinventoryng'][69] = "Nouvel ordinateur créé dans GLPI";
$LANG['plugin_ocsinventoryng'][70] = "Ordinateurs importés";
$LANG['plugin_ocsinventoryng'][71] = "Ordinateurs synchronisés";
$LANG['plugin_ocsinventoryng'][72] = "Ordinateurs ne vérifiant aucune règle";
$LANG['plugin_ocsinventoryng'][73] = "Ordinateurs liés";
$LANG['plugin_ocsinventoryng'][74] = "Ordinateurs non modifiés";
$LANG['plugin_ocsinventoryng'][75] = "Ordinateurs en doublon";
$LANG['plugin_ocsinventoryng'][76] = "Statistiques de la liaison OCSNG";
$LANG['plugin_ocsinventoryng'][77] = "traitement terminé";
$LANG['plugin_ocsinventoryng'][78] = "Liaison si possible, sinon import refusé";
$LANG['plugin_ocsinventoryng'][79] = "Liaison si possible";
$LANG['plugin_ocsinventoryng'][80] = "Ordinateurs dont l'import est refusé par une règle";

$LANG['plugin_ocsinventoryng']['config'][1]  = "Utilisateur de la base de données OCSNG";
$LANG['plugin_ocsinventoryng']['config'][2]  = "Hôte de la base de données OCSNG";
$LANG['plugin_ocsinventoryng']['config'][3]  = "Mot de passe de l'utilisateur OCSNG";
$LANG['plugin_ocsinventoryng']['config'][4]  = "Nom de la base de données OCSNG";
$LANG['plugin_ocsinventoryng']['config'][5]  = "Options d'importation";
$LANG['plugin_ocsinventoryng']['config'][6] = "Connexion à la base de données OCSNG";
$LANG['plugin_ocsinventoryng']['config'][7]  = "Base de données OCSNG en UTF8";
$LANG['plugin_ocsinventoryng']['config'][8] = "Ajout impossible. Le serveur OCSNG existe déjà.";
$LANG['plugin_ocsinventoryng']['config'][9]  = "Exclure les tags suivants (séparateur $, rien pour aucun)";
$LANG['plugin_ocsinventoryng']['config'][10] = "Import global";
$LANG['plugin_ocsinventoryng']['config'][11] = "Pas d'import";
$LANG['plugin_ocsinventoryng']['config'][12] = "Import unique";
$LANG['plugin_ocsinventoryng']['config'][13] = "Import unique : tout est importé tel quel";
$LANG['plugin_ocsinventoryng']['config'][14] = "Import global : tout est importé mais le matériel est géré de manière globale (sans doublons)";
$LANG['plugin_ocsinventoryng']['config'][15] = "Pas d'import : GLPI n'importera pas ces éléments";
$LANG['plugin_ocsinventoryng']['config'][16] = "Statut par défaut";
$LANG['plugin_ocsinventoryng']['config'][17] = "Limiter l'importation aux tags suivants (séparateur $, rien pour tous)";
$LANG['plugin_ocsinventoryng']['config'][18] = "Assurez-vous au préalable d'avoir géré correctement les doublons dans OCSNG";
$LANG['plugin_ocsinventoryng']['config'][19] = "Import unique sur numéro de série";
$LANG['plugin_ocsinventoryng']['config'][20] = "Comportement à la suppression d'un ordinateur dans OCSNG";

$LANG['plugin_ocsinventoryng']['config'][27] = "Informations générales";
$LANG['plugin_ocsinventoryng']['config'][28]  = "Synchronisation des matériels depuis OCSNG";
$LANG['plugin_ocsinventoryng']['config'][36] = "Modems";
$LANG['plugin_ocsinventoryng']['config'][37] = "Ports";
$LANG['plugin_ocsinventoryng']['config'][38] = "Utiliser le dictionnaire logiciel d'OCSNG";
$LANG['plugin_ocsinventoryng']['config'][39] = "TAG OCSNG";
$LANG['plugin_ocsinventoryng']['config'][40] = "Nombre d'éléments à synchroniser via l'action automatique ocsng";
$LANG['plugin_ocsinventoryng']['config'][41] = "Base de registre";

$LANG['plugin_ocsinventoryng']['config'][43] = "Informations administratives OCSNG";

$LANG['plugin_ocsinventoryng']['config'][48] = "Comportement lors de la déconnexion";
$LANG['plugin_ocsinventoryng']['config'][49] = "Corbeille";
$LANG['plugin_ocsinventoryng']['config'][50] = "Suppression";

$LANG['plugin_ocsinventoryng']['config'][52] = "Liaison automatique des ordinateurs";
$LANG['plugin_ocsinventoryng']['config'][53] = "Activer la liaison automatique";
$LANG['plugin_ocsinventoryng']['config'][54] = "Critères d'existence d'un ordinateur";
$LANG['plugin_ocsinventoryng']['config'][55] = "Chercher les ordinateurs dans GLPI dont le statut est";
$LANG['plugin_ocsinventoryng']['config'][56] = "vide";
$LANG['plugin_ocsinventoryng']['config'][57] = "égal";
$LANG['plugin_ocsinventoryng']['config'][58] = "La liaison fusionne automatiquement un ordinateur GLPI avec un d'OCSNG.<br>Cette option est prise en compte lors de la liaison manuelle et par les scripts de synchronisation.";
$LANG['plugin_ocsinventoryng']['config'][59] = "Chemin d'accès web de la console OCSNG";

$LANG['plugin_ocsinventoryng']['profile'][0] = "Gestion des droits";
$LANG['plugin_ocsinventoryng']['profile'][1] = "OCSNG";
$LANG['plugin_ocsinventoryng']['profile'][2] = "Synchronisation OCSNG manuellement";
$LANG['plugin_ocsinventoryng']['profile'][3] = "Voir les informations OCSNG";

$LANG['plugin_ocsinventoryng']['registry'][1] = "Ruche";
$LANG['plugin_ocsinventoryng']['registry'][2] = "Chemin";
$LANG['plugin_ocsinventoryng']['registry'][3] = "Clé/valeur";
$LANG['plugin_ocsinventoryng']['registry'][4] = "Clé(s) de registre trouvée(s)";
$LANG['plugin_ocsinventoryng']['registry'][5] = "Aucune clé de la base de registre trouvée";
$LANG['plugin_ocsinventoryng']['registry'][6] = "Nom OCSNG";
//massocsimport

$LANG['plugin_ocsinventoryng']["common"][1]  = "Exécution des scripts de la tâche automatique";
$LANG['plugin_ocsinventoryng']["common"][2]  = "Date de début d'exécution";
$LANG['plugin_ocsinventoryng']["common"][3]  = "Date de fin d'exécution";
$LANG['plugin_ocsinventoryng']["common"][4]  = "Nombre de threads";
$LANG['plugin_ocsinventoryng']["common"][5]  = "Machines importées par la tâche automatique";
$LANG['plugin_ocsinventoryng']["common"][6]  = "Machines synchronisées";
$LANG['plugin_ocsinventoryng']["common"][7]  = "Durée d'exécution du script";
$LANG['plugin_ocsinventoryng']["common"][8]  = "Informations sur le script";
$LANG['plugin_ocsinventoryng']["common"][9]  = "Thread";
$LANG['plugin_ocsinventoryng']["common"][10] = "Statut";
$LANG['plugin_ocsinventoryng']["common"][11] = "Serveur OCS";
$LANG['plugin_ocsinventoryng']["common"][12] = "Total de machines traitées";
$LANG['plugin_ocsinventoryng']["common"][15] = "Pourcentage effectué";
$LANG['plugin_ocsinventoryng']["common"][17] = "Total de machines à traiter";
$LANG['plugin_ocsinventoryng']["common"][18] = "Machines non importées par la tâche automatique";
$LANG['plugin_ocsinventoryng']["common"][19] = "Machines liées";
$LANG['plugin_ocsinventoryng']["common"][20] = "Machines non modifiées";
$LANG['plugin_ocsinventoryng']["common"][21] = "Machines importées par la tâche automatique";
$LANG['plugin_ocsinventoryng']["common"][22] = "Device ID";
$LANG['plugin_ocsinventoryng']["common"][23] = "Liste des machines non importées";
$LANG['plugin_ocsinventoryng']["common"][24] = "Dernière remontée dans OCS";
$LANG['plugin_ocsinventoryng']["common"][25] = "Minimum";
$LANG['plugin_ocsinventoryng']["common"][26] = "Maximum";
$LANG['plugin_ocsinventoryng']["common"][27] = "Moyenne";
$LANG['plugin_ocsinventoryng']["common"][28] = "Total";
$LANG['plugin_ocsinventoryng']["common"][29] = "Vitesse";
$LANG['plugin_ocsinventoryng']["common"][30] = "Statistiques";
$LANG['plugin_ocsinventoryng']["common"][31] = "Date de traitement";
$LANG['plugin_ocsinventoryng']["common"][32] = "Processus";
$LANG['plugin_ocsinventoryng']["common"][33] = "Machines non uniques";
$LANG['plugin_ocsinventoryng']["common"][34] = "Cause du rejet";
$LANG['plugin_ocsinventoryng']["common"][35] = "Impossible d'affecter une entité";
$LANG['plugin_ocsinventoryng']["common"][36] = "Critères d'unicité non vérifiés";
$LANG['plugin_ocsinventoryng']["common"][37] = "Import explicitement refusé par une règle";
$LANG['plugin_ocsinventoryng']["common"][38] = "Machines refusées";
$LANG['plugin_ocsinventoryng']["common"][39] = "Règle(s) vérifiée(s)";
$LANG['plugin_ocsinventoryng']["common"][40] = "Entité";
$LANG['plugin_ocsinventoryng']["common"][41] = "Import";

$LANG['plugin_ocsinventoryng']["config"][101]  = "Configuration du plugin d'import OCS";
$LANG['plugin_ocsinventoryng']["config"][102]  = "Nettoyage des processus";
$LANG['plugin_ocsinventoryng']["config"][105]  = "Afficher les processus où rien n'a été modifié";
$LANG['plugin_ocsinventoryng']["config"][106]  = "Effacer les processus après";
$LANG['plugin_ocsinventoryng']["config"][107]  = "Contrôle";
$LANG['plugin_ocsinventoryng']["config"][108]  = "le script d'import OCS";
$LANG['plugin_ocsinventoryng']["config"][109]  = "Verrouillage désactivé";
$LANG['plugin_ocsinventoryng']["config"][110] = "Verrouillage activé";
$LANG['plugin_ocsinventoryng']["config"][114] = "Rafraîchir les informations d'un processus toutes les ";
$LANG['plugin_ocsinventoryng']["config"][116] = "Affichage";
$LANG['plugin_ocsinventoryng']["config"][117] = "Activer le verrouillage";
$LANG['plugin_ocsinventoryng']["config"][118] = "Désactiver le verrouillage";
$LANG['plugin_ocsinventoryng']["config"][121] = "Serveur OCS par défaut";
$LANG['plugin_ocsinventoryng']["config"][122] = "Tous les serveurs";

$LANG['plugin_ocsinventoryng']["setup"][0] = "Le mode OCSNG n'est pas activé";
$LANG['plugin_ocsinventoryng']["setup"][1] = "Aucun server OCS n'est configuré";
$LANG['plugin_ocsinventoryng']["setup"][2] = "Configuration";
$LANG['plugin_ocsinventoryng']["setup"][3] = "Autoriser la mise à jour OCS";
$LANG['plugin_ocsinventoryng']["setup"][8] = "Mode d'emploi";

$LANG['plugin_ocsinventoryng']["time"][1] = "heures";
$LANG['plugin_ocsinventoryng']["time"][3] = "sec";

$LANG['plugin_ocsinventoryng']["display"][1] = "Importer dans l'entité";
$LANG['plugin_ocsinventoryng']["display"][4] = "Retour à la liste des scripts";
$LANG['plugin_ocsinventoryng']["display"][5] = "Supprimer la machine d'OCS";
$LANG['plugin_ocsinventoryng']["display"][6] = "Lier la machine à une autre présente dans GLPI";
$LANG['plugin_ocsinventoryng']["display"][7] = "Action à réaliser sur la machine";

$LANG['plugin_ocsinventoryng']["notimported"][1] = "Machine non importée";
$LANG['plugin_ocsinventoryng']["notimported"][2] = "Console OCS";
$LANG['plugin_ocsinventoryng']["notimported"][3] = "Relancer l'import";
$LANG['plugin_ocsinventoryng']["notimported"][4] = "L'import a échoué";

?>