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
$string['cachedef_sessions'] = 'Cache des identifiants de session du chat du tuteur IA';
$string['char'] = 'caractère';
$string['chars'] = 'caractères';
$string['clear_selection'] = 'Effacer la sélection';
$string['close'] = 'Fermer le tuteur IA';
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
$string['enable_tutor_for_course'] = 'Activer le tuteur IA pour ce cours';
$string['enable_tutor_for_course_help'] = 'Lorsqu\'il est activé, le tuteur IA sera disponible pour les étudiants et les enseignants dans ce cours. Le paramètre global du plugin doit également être activé.';
$string['enabled'] = 'Activer le chat';
$string['enabled_desc'] = 'Activer ou désactiver le chat du tuteur IA globalement';
$string['error_api_not_configured'] = 'La configuration de l\'API est manquante. Veuillez vérifier vos paramètres.';
$string['error_attempt_later'] = 'Une erreur s\'est produite. Veuillez réessayer plus tard.';
$string['error_history_unavailable'] = 'La conversation précédente n\'a pas pu être chargée. Vous pouvez continuer à discuter.';
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
$string['error_no_credits_fallback'] = 'Crédits insuffisants : {$a}';
$string['error_tutor_not_available'] = 'Le tuteur IA n\'est pas disponible pour ce cours.';
$string['error_unexpected'] = 'Une erreur inattendue s\'est produite. Veuillez réessayer.';
$string['error_unknown'] = 'Une erreur inconnue s\'est produite. Veuillez réessayer.';
$string['include_grades'] = 'Envoyer les notes de l\'étudiant au tuteur IA';
$string['include_grades_desc'] = 'Lorsque cette option est activée, les notes de l\'étudiant dans le cours sont ajoutées au contexte envoyé au service Datacurso AI afin que le tuteur puisse répondre aux questions à leur sujet. Désactivée par défaut pour minimiser les données personnelles transférées.';
$string['line'] = 'ligne';
$string['lines'] = 'lignes';
$string['loading'] = 'Chargement...';
$string['manage_tutor'] = 'Gestion du tuteur IA';
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
$string['privacy:export:course_config'] = 'Configuration du tuteur pour le cours';
$string['privacy:export:sessions'] = 'Sessions de chat';
$string['privacy:metadata:datacurso_ai'] = 'Les messages du chat et le contexte du cours sont envoyés au service Datacurso AI pour générer les réponses du tuteur.';
$string['privacy:metadata:datacurso_ai:cmid'] = 'L\'identifiant du module de cours consulté par l\'utilisateur lors de la rédaction du message.';
$string['privacy:metadata:datacurso_ai:course_structure'] = 'La structure du cours visible par l\'utilisateur (activités, sections, dates et notes maximales).';
$string['privacy:metadata:datacurso_ai:custom_prompt'] = 'Les instructions institutionnelles personnalisées configurées par l\'administrateur.';
$string['privacy:metadata:datacurso_ai:grades'] = 'Les notes de l\'utilisateur dans le cours, uniquement lorsque le réglage « Envoyer les notes de l\'étudiant » est activé.';
$string['privacy:metadata:datacurso_ai:lang'] = 'La langue actuelle de l\'utilisateur.';
$string['privacy:metadata:datacurso_ai:messages'] = 'Les messages du chat rédigés par l\'utilisateur et les réponses précédentes du tuteur.';
$string['privacy:metadata:datacurso_ai:page_url'] = 'L\'URL de la page Moodle depuis laquelle le message a été envoyé.';
$string['privacy:metadata:datacurso_ai:selected_text'] = 'Le texte sélectionné par l\'utilisateur sur la page pour poser sa question.';
$string['privacy:metadata:datacurso_ai:site_id'] = 'L\'identifiant anonyme de ce site Moodle.';
$string['privacy:metadata:datacurso_ai:site_url'] = 'L\'URL de ce site Moodle.';
$string['privacy:metadata:datacurso_ai:timezone'] = 'Le fuseau horaire de l\'utilisateur.';
$string['privacy:metadata:datacurso_ai:userid'] = 'L\'identifiant de l\'utilisateur qui envoie le message.';
$string['privacy:metadata:local_dttutor_course_config'] = 'Configuration du tuteur IA par cours.';
$string['privacy:metadata:local_dttutor_course_config:timemodified'] = 'Date de la dernière modification de la configuration.';
$string['privacy:metadata:local_dttutor_course_config:usermodified'] = 'L\'identifiant de l\'utilisateur ayant modifié la configuration en dernier.';
$string['privacy:metadata:local_dttutor_session'] = 'Identifiants des sessions de chat ouvertes par un utilisateur avec le tuteur Datacurso AI, stockés afin de pouvoir les supprimer à distance.';
$string['privacy:metadata:local_dttutor_session:cmid'] = 'L\'identifiant du module de cours auquel appartient la session (0 pour l\'ensemble du cours).';
$string['privacy:metadata:local_dttutor_session:courseid'] = 'L\'identifiant du cours auquel appartient la session.';
$string['privacy:metadata:local_dttutor_session:remotesessionid'] = 'L\'identifiant de la session dans le service Datacurso AI.';
$string['privacy:metadata:local_dttutor_session:timecreated'] = 'Date de création de la session.';
$string['privacy:metadata:local_dttutor_session:timemodified'] = 'Date du dernier renouvellement de la session.';
$string['privacy:metadata:local_dttutor_session:userid'] = 'L\'identifiant de l\'utilisateur propriétaire de la session.';
$string['ref_bottom'] = 'Bas';
$string['ref_left'] = 'Gauche';
$string['ref_right'] = 'Droite';
$string['ref_top'] = 'Haut';
$string['reference_edge_x'] = 'Bord de référence horizontal';
$string['reference_edge_y'] = 'Bord de référence vertical';
$string['selected'] = 'sélectionné';
$string['sendmessage'] = 'Envoyer le message';
$string['student'] = 'Étudiant';
$string['teacher'] = 'Enseignant';
$string['tutor_disabled_notice'] = 'Le tuteur IA est actuellement désactivé pour ce cours. Les étudiants ne verront pas l\'interface de chat.';
$string['tutor_status'] = 'Statut du tuteur IA';
$string['tutorcustomization'] = 'Personnalisation du tuteur';
$string['tutorname_default'] = 'Tuteur IA';
$string['tutorname_setting'] = 'Nom du tuteur';
$string['tutorname_setting_desc'] = 'Configurez le nom à afficher dans l\'en-tête du chat. Vous pouvez utiliser {teachername} pour afficher le vrai nom de l\'enseignant du cours, ou entrer un nom personnalisé. Exemples : "{teachername}" affichera "Jean Dupont", "Assistant IA" affichera "Assistant IA".';
$string['typemessage'] = 'Tapez votre message...';
$string['welcomemessage_default'] = 'Bonjour ! Je suis {teachername}, votre assistant IA. Comment puis-je vous aider aujourd\'hui ?';
$string['welcomemessage_setting'] = 'Message de bienvenue';
$string['welcomemessage_setting_desc'] = 'Personnalisez le message de bienvenue affiché à l\'ouverture du chat. Vous pouvez utiliser des marqueurs : {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Hier';
