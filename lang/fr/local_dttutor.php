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

$string['accepted_files_pdf_only'] = 'Seuls les fichiers PDF sont acceptés (maximum 50 fichiers)';
$string['avatar'] = 'Avatar du tuteur IA';
$string['avatar_desc'] = 'Sélectionnez l\'avatar à afficher sur le bouton de chat flottant du tuteur IA. Si aucun n\'est sélectionné ou si le fichier n\'existe pas, l\'Avatar 1 sera utilisé par défaut.';
$string['avatar_position'] = 'Position de l\'avatar';
$string['avatar_position_desc'] = 'Configurez où le bouton flottant de l\'avatar du tuteur IA sera affiché. Choisissez une position prédéfinie dans un coin ou personnalisez les coordonnées X,Y exactes. L\'aperçu en direct montre comment il apparaîtra.';
$string['cachedef_sessions'] = 'Cache pour les sessions de chat du tuteur IA';
$string['char'] = 'caractère';
$string['chars'] = 'caractères';
$string['choose_files'] = 'Choisir des fichiers';
$string['clear_selection'] = 'Effacer la sélection';
$string['close'] = 'Fermer le tuteur IA';
$string['configuration_error'] = 'Erreur de configuration';
$string['configure_now'] = 'Configurer maintenant';
$string['connection_interrupted'] = '[Connexion interrompue]';
$string['course_custom_prompt'] = 'Prompt personnalisé spécifique au cours';
$string['course_custom_prompt_help'] = 'Ce prompt personnalisé remplace le paramètre global pour ce cours uniquement. Laissez vide pour utiliser le prompt global.';
$string['course_materials'] = 'Matériel de cours (PDF)';
$string['course_materials_help'] = 'Téléchargez des fichiers PDF supplémentaires que le tuteur IA devrait consulter pour répondre aux questions.';
$string['custom_prompt'] = 'Prompt personnalisé';
$string['custom_prompt_desc'] = 'Instructions personnalisées pour contrôler le comportement du tuteur IA. Utilisez ce champ pour fournir des directives spécifiques, un ton ou des limites de connaissances pour le tuteur.';
$string['customavatar'] = 'Avatar personnalisé';
$string['customavatar_desc'] = 'Téléchargez votre propre image d\'avatar personnalisée. Cela remplacera l\'avatar prédéfini sélectionné.';
$string['customavatar_dimensions'] = 'Dimensions recommandées : 200x200 pixels. Formats supportés : PNG, JPG, JPEG, SVG. Taille maximale du fichier : 512KB.';
$string['drag_drop_upload'] = 'Glissez et déposez des fichiers PDF ici';
$string['drag_drop_upload_or_browse'] = 'ou cliquez pour parcourir';
$string['drawer_side'] = 'Côté d\'ouverture du tiroir';
$string['drawer_side_help'] = 'Choisissez de quel côté le tiroir de chat s\'ouvrira. C\'est indépendant de la position du bouton d\'avatar.';
$string['drawer_side_left'] = 'Ouvrir depuis la gauche';
$string['drawer_side_right'] = 'Ouvrir depuis la droite';
$string['dttutor:use'] = 'Utiliser le tuteur IA';
$string['enable_tutor_for_course'] = 'Activer le tuteur IA pour ce cours';
$string['enable_tutor_for_course_help'] = 'Lorsqu\'il est activé, le tuteur IA sera disponible pour les étudiants et les enseignants dans ce cours. Le paramètre global du plugin doit également être activé.';
$string['enabled'] = 'Activer le chat';
$string['enabled_desc'] = 'Activer ou désactiver le chat du tuteur IA globalement';
$string['error_api_not_configured'] = 'La configuration de l\'API est manquante. Veuillez vérifier vos paramètres.';
$string['error_api_request_failed'] = 'Erreur de requête API : {$a}';
$string['error_attempt_later'] = 'Une erreur s\'est produite. Veuillez réessayer plus tard.';
$string['error_cache_unavailable'] = 'Le service de chat est temporairement indisponible. Veuillez essayer de rafraîchir la page.';
$string['error_empty_message'] = 'Le message ne peut pas être vide';
$string['error_establish_sse_connection'] = '[Erreur] Impossible d\'établir la connexion SSE';
$string['error_http_code'] = 'Erreur HTTP {$a}';
$string['error_insufficient_tokens'] = 'Il n\'y a pas assez de crédits IA disponibles pour traiter votre demande. Veuillez contacter votre administrateur pour ajouter plus de crédits et continuer à utiliser le tuteur IA.';
$string['error_insufficient_tokens_short'] = 'Crédits insuffisants';
$string['error_internal'] = 'Erreur interne : {$a}';
$string['error_invalid_api_response'] = 'Réponse API invalide';
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
$string['error_webservice_not_configured'] = 'Le chat du tuteur IA n\'est pas correctement configuré et est actuellement indisponible.';
$string['error_webservice_not_configured_action'] = 'Veuillez contacter votre administrateur de site ou signaler ce problème pour activer le service de chat.';
$string['error_webservice_not_configured_admin'] = 'Le service web du fournisseur d\'IA Datacurso doit être configuré avant d\'utiliser le tuteur IA. <a href="{$a}" target="_blank">Cliquez ici pour le configurer maintenant</a>.';
$string['error_webservice_not_configured_admin_inline'] = 'Le service web du fournisseur d\'IA Datacurso doit être configuré avant d\'utiliser le tuteur IA.';
$string['error_webservice_not_configured_short'] = 'Service de chat indisponible';
$string['last_indexed'] = 'Dernière synchronisation : {$a}';
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
$string['or_click_to_browse'] = 'ou cliquez pour parcourir';
$string['pluginname'] = 'Tuteur IA';
$string['position_custom'] = 'Position personnalisée';
$string['position_left'] = 'Coin inférieur gauche';
$string['position_preset'] = 'Position prédéfinie';
$string['position_right'] = 'Coin inférieur droit';
$string['position_x'] = 'Position horizontale (X)';
$string['position_x_help'] = 'Distance depuis le bord gauche. Exemples : 2rem, 20px, 5%. Utilisez des valeurs négatives pour positionner depuis le bord droit.';
$string['position_x_label'] = 'X : {$a->value} (depuis {$a->ref})';
$string['position_y'] = 'Position verticale (Y)';
$string['position_y_help'] = 'Distance depuis le bord inférieur. Exemples : 6rem, 80px, 10%. Utilisez des valeurs négatives pour positionner depuis le bord supérieur.';
$string['position_y_label'] = 'Y : {$a->value} (depuis {$a->ref})';
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
$string['selection_indicator'] = '{$a} lignes sélectionnées';
$string['selectionformat'] = '{$a->lines} {$a->linetext}, {$a->chars} {$a->chartext} sélectionné';
$string['sendmessage'] = 'Envoyer le message';
$string['sessionnotready'] = 'La session du tuteur IA n\'est pas prête. Veuillez réessayer.';
$string['student'] = 'Étudiant';
$string['teacher'] = 'Enseignant';
$string['tutor_disabled_notice'] = 'Le tuteur IA est actuellement désactivé pour ce cours. Les étudiants ne verront pas l\'interface de chat.';
$string['tutor_status'] = 'Statut du tuteur IA';
$string['tutorcustomization'] = 'Personnalisation du tuteur';
$string['tutorname_default'] = 'Tuteur IA';
$string['tutorname_setting'] = 'Nom du tuteur';
$string['tutorname_setting_desc'] = 'Configurez le nom à afficher dans l\'en-tête du chat. Vous pouvez utiliser {teachername} pour afficher le vrai nom de l\'enseignant du cours, ou entrer un nom personnalisé. Exemples : "{teachername}" affichera "Jean Dupont", "Assistant IA" affichera "Assistant IA".';
$string['typemessage'] = 'Tapez votre message...';
$string['unauthorized'] = 'Accès non autorisé';
$string['upload_files'] = 'Télécharger des fichiers';
$string['upload_material'] = 'Télécharger PDF';
$string['welcomemessage'] = 'Bonjour ! Je suis votre assistant IA. Comment puis-je vous aider aujourd\'hui ?';
$string['welcomemessage_default'] = 'Bonjour ! Je suis {teachername}, votre assistant IA. Comment puis-je vous aider aujourd\'hui ?';
$string['welcomemessage_setting'] = 'Message de bienvenue';
$string['welcomemessage_setting_desc'] = 'Personnalisez le message de bienvenue affiché à l\'ouverture du chat. Vous pouvez utiliser des marqueurs : {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Hier';
