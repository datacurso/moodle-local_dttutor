<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * French language strings for Tutor-IA plugin.
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['avatar'] = 'Avatar du tuteur IA';
$string['avatar_desc'] = 'Sélectionnez l\'avatar à afficher sur le bouton de chat flottant du tuteur IA. Si aucun n\'est sélectionné ou si le fichier n\'existe pas, l\'Avatar 1 sera utilisé par défaut.';
$string['avatar_position'] = 'Position de l\'avatar';
$string['avatar_position_desc'] = 'Configurez où le bouton flottant de l\'avatar du tuteur IA sera affiché. Choisissez une position prédéfinie dans un coin ou personnalisez les coordonnées X,Y exactes. L\'aperçu en direct montre comment il apparaîtra.';
$string['cachedef_course_knowledge'] = 'Cache des connaissances de cours préchargées utilisées par le chat';
$string['cachedef_schema_cache'] = 'Cache des schémas des fonctions de service web Moodle';
$string['char'] = 'caractère';
$string['chars'] = 'caractères';
$string['clear_selection'] = 'Effacer la sélection';
$string['close'] = 'Fermer le tuteur IA';
$string['configuration_error'] = 'Erreur de configuration';
$string['connection_interrupted'] = '[Connexion interrompue]';
$string['course_materials'] = 'Matériel de cours (PDF)';
$string['ctx_loc_activity'] = 'Emplacement : Activité';
$string['ctx_loc_admin'] = 'Emplacement : Administration';
$string['ctx_loc_calendar'] = 'Emplacement : Calendrier';
$string['ctx_loc_course'] = 'Emplacement : Cours';
$string['ctx_loc_dashboard'] = 'Emplacement : Tableau de bord';
$string['ctx_loc_files'] = 'Emplacement : Fichiers';
$string['ctx_loc_gradebook'] = 'Emplacement : Carnet de notes';
$string['ctx_loc_messages'] = 'Emplacement : Messages';
$string['ctx_loc_profile'] = 'Emplacement : Profil';
$string['custom_prompt'] = 'Prompt personnalisé';
$string['custom_prompt_desc'] = 'Instructions personnalisées pour contrôler le comportement du tuteur IA. Utilisez ce champ pour fournir des directives spécifiques, un ton ou des limites de connaissances pour le tuteur.';
$string['customavatar'] = 'Avatar personnalisé';
$string['customavatar_desc'] = 'Téléchargez votre propre image d\'avatar personnalisée. Cela remplacera l\'avatar prédéfini sélectionné.';
$string['customavatar_dimensions'] = 'Dimensions recommandées : 200x200 pixels. Formats supportés : PNG, JPG, JPEG, SVG. Taille maximale du fichier : 512KB.';
$string['drawer_side'] = 'Côté d\'ouverture du tiroir';
$string['drawer_side_help'] = 'Choisissez de quel côté le tiroir de chat s\'ouvrira. C\'est indépendant de la position du bouton d\'avatar.';
$string['drawer_side_left'] = 'Ouvrir depuis la gauche';
$string['drawer_side_right'] = 'Ouvrir depuis la droite';
$string['dttutor:use'] = 'Utiliser le tuteur IA';
$string['edit_cancel'] = 'Annuler';
$string['edit_message'] = 'Modifier le message';
$string['edit_save'] = 'Enregistrer';
$string['editing_message'] = 'Modification du message';
$string['enable_tutor_for_course'] = 'Activer le tuteur IA pour ce cours';
$string['enable_tutor_for_course_help'] = 'Lorsqu\'il est activé, le tuteur IA sera disponible pour les étudiants et les enseignants dans ce cours. Le paramètre global du plugin doit également être activé.';
$string['enabled'] = 'Activer le chat';
$string['enabled_desc'] = 'Activer ou désactiver le chat du tuteur IA globalement';
$string['error_api_not_configured'] = 'La configuration de l\'API est manquante. Veuillez vérifier vos paramètres.';
$string['error_attempt_later'] = 'Une erreur s\'est produite. Veuillez réessayer plus tard.';
$string['error_empty_message'] = 'Le message ne peut pas être vide';
$string['error_establish_sse_connection'] = '[Erreur] Impossible d\'établir la connexion SSE';
$string['error_insufficient_tokens'] = 'Il n\'y a pas assez de crédits IA disponibles pour traiter votre demande. Veuillez contacter votre administrateur pour ajouter plus de crédits et continuer à utiliser le tuteur IA.';
$string['error_insufficient_tokens_short'] = 'Crédits insuffisants';
$string['error_internal'] = 'Erreur interne : {$a}';
$string['error_invalid_coordinates'] = 'Coordonnées invalides. Veuillez utiliser des valeurs CSS valides (ex. 10px, 2rem, 50%)';
$string['error_invalid_message'] = 'Veuillez entrer un message valide';
$string['error_invalid_position'] = 'Données de position invalides';
$string['error_license_fallback'] = 'Erreur de licence : {$a}';
$string['error_license_fallback_short'] = 'Erreur de licence';
$string['error_license_not_allowed'] = 'Votre licence ne permet pas l\'accès au service Tuteur IA. Veuillez contacter votre administrateur pour vérifier l\'état de votre licence ou mettre à niveau votre plan.';
$string['error_license_not_allowed_short'] = 'Erreur de licence';
$string['error_message_too_long'] = '[Erreur] Le message est trop long. Maximum 4000 caractères.';
$string['error_metadata_too_large'] = 'Les métadonnées envoyées avec votre message sont trop volumineuses. Veuillez réessayer.';
$string['error_no_credits'] = 'Pas assez de crédits IA disponibles.';
$string['error_no_credits_fallback'] = 'Crédits insuffisants : {$a}';
$string['error_no_credits_short'] = 'Aucun crédit disponible';
$string['error_selected_text_too_large'] = 'Le texte sélectionné est trop grand. Veuillez sélectionner une portion plus petite.';
$string['error_unexpected'] = 'Une erreur inattendue s\'est produite. Veuillez réessayer.';
$string['error_unknown'] = 'Une erreur inconnue s\'est produite. Veuillez réessayer.';
$string['line'] = 'ligne';
$string['lines'] = 'lignes';
$string['loading'] = 'Chargement...';
$string['manage_tutor'] = 'Gestion du tuteur IA';
$string['material_deleted'] = 'Matériel supprimé avec succès';
$string['material_uploaded'] = 'Matériel téléchargé avec succès';
$string['off_topic_detection_enabled'] = 'Activer la détection hors sujet';
$string['off_topic_detection_enabled_desc'] = 'Lorsqu\'activé, le tuteur IA détectera et répondra aux messages hors sujet selon le niveau de rigueur configuré ci-dessous.';
$string['off_topic_strictness'] = 'Rigueur hors sujet';
$string['off_topic_strictness_desc'] = 'Contrôlez la rigueur de la détection hors sujet. Permissif offre plus de flexibilité, tandis que strict impose des conversations liées uniquement au cours.';
$string['off_topic_strictness_moderate'] = 'Modéré';
$string['off_topic_strictness_permissive'] = 'Permissif';
$string['off_topic_strictness_strict'] = 'Strict';
$string['open'] = 'Ouvrir le tuteur IA';
$string['pluginname'] = 'Tuteur IA';
$string['position_custom'] = 'Position personnalisée';
$string['position_left'] = 'Coin inférieur gauche';
$string['position_preset'] = 'Position prédéfinie';
$string['position_right'] = 'Coin inférieur droit';
$string['position_x'] = 'Position horizontale (X)';
$string['position_x_help'] = 'Distance depuis le bord gauche. Exemples : 2rem, 20px, 5%. Utilisez des valeurs négatives pour positionner depuis le bord droit.';
$string['position_y'] = 'Position verticale (Y)';
$string['position_y_help'] = 'Distance depuis le bord inférieur. Exemples : 6rem, 80px, 10%. Utilisez des valeurs négatives pour positionner depuis le bord supérieur.';
$string['positiondisplay_corner'] = 'Position : coin {$a->preset} | Tiroir : {$a->drawer}';
$string['positiondisplay_custom'] = 'Position : X : {$a->x}, Y : {$a->y} | Tiroir : {$a->drawer}';
$string['preview'] = 'Aperçu en direct';
$string['ref_bottom'] = 'Bas';
$string['ref_left'] = 'Gauche';
$string['ref_right'] = 'Droite';
$string['ref_top'] = 'Haut';
$string['reference_edge_x'] = 'Bord de référence horizontal';
$string['reference_edge_y'] = 'Bord de référence vertical';
$string['selected'] = 'sélectionné';
$string['sendmessage'] = 'Envoyer le message';
$string['servicebot_firstname'] = 'Tuteur';
$string['servicebot_lastname'] = 'IA';
$string['sessionnotready'] = 'La session du tuteur IA n\'est pas prête. Veuillez réessayer.';
$string['student'] = 'Étudiant';
$string['teacher'] = 'Enseignant';
$string['tool_call_webservice_desc'] = 'Appelle une fonction de service web Moodle. La fonction ne fonctionnera que si l\'utilisateur dispose des autorisations requises dans Moodle.';
$string['tool_call_webservice_function'] = 'Le nom de la fonction de service web (par ex. core_user_get_users, core_course_get_courses).';
$string['tool_call_webservice_params'] = 'Paramètres à transmettre à la fonction sous forme de paires clé-valeur.';
$string['tool_ws_describe_desc'] = 'Obtient la documentation complète d\'une fonction de service web Moodle spécifique : description, paramètres avec leurs types et structure de retour.';
$string['tool_ws_describe_wsname'] = 'Nom exact de la fonction de service web (par ex. core_user_create_users, core_course_get_courses).';
$string['tool_ws_search_desc'] = 'Recherche des fonctions de service web Moodle par intention ou mots-clés. Renvoie le nom de la fonction, le composant, la description, les paramètres et les valeurs de retour.';
$string['tool_ws_search_limit'] = 'Nombre maximal de résultats (par défaut : 20, max : 30).';
$string['tool_ws_search_query'] = 'Intention ou mots-clés en ANGLAIS (par ex. "create user", "enrol student", "get course grades").';
$string['tutor_disabled_notice'] = 'Le tuteur IA est actuellement désactivé pour ce cours. Les étudiants ne verront pas l\'interface de chat.';
$string['tutor_status'] = 'Statut du tuteur IA';
$string['tutorcustomization'] = 'Personnalisation du tuteur';
$string['tutorname_default'] = 'Tuteur IA';
$string['tutorname_setting'] = 'Nom du tuteur';
$string['tutorname_setting_desc'] = 'Configurez le nom à afficher dans l\'en-tête du chat. Vous pouvez utiliser {teachername} pour afficher le vrai nom de l\'enseignant du cours, ou entrer un nom personnalisé. Exemples : "{teachername}" affichera "Jean Dupont", "Assistant IA" affichera "Assistant IA".';
$string['typemessage'] = 'Tapez votre message...';
$string['welcomemessage'] = 'Bonjour ! Je suis votre assistant IA. Comment puis-je vous aider aujourd\'hui ?';
$string['welcomemessage_default'] = 'Bonjour ! Je suis {teachername}, votre assistant IA. Comment puis-je vous aider aujourd\'hui ?';
$string['welcomemessage_setting'] = 'Message de bienvenue';
$string['welcomemessage_setting_desc'] = 'Personnalisez le message de bienvenue affiché à l\'ouverture du chat. Vous pouvez utiliser des marqueurs : {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Hier';
