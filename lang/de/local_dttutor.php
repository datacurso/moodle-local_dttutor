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
 * German language strings for Tutor-IA plugin.
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accepted_files_pdf_only'] = 'Nur PDF-Dateien werden akzeptiert (maximal 50 Dateien)';
$string['avatar'] = 'KI-Tutor Avatar';
$string['avatar_desc'] = 'Wählen Sie den Avatar, der auf der schwebenden Chat-Schaltfläche des KI-Tutors angezeigt werden soll. Wenn keiner ausgewählt ist oder die Datei nicht existiert, wird standardmäßig Avatar 1 verwendet.';
$string['avatar_position'] = 'Avatar-Position';
$string['avatar_position_desc'] = 'Konfigurieren Sie, wo die schwebende Avatar-Schaltfläche angezeigt werden soll. Wählen Sie eine voreingestellte Eckposition oder passen Sie die genauen X,Y-Koordinaten an. Die Live-Vorschau zeigt, wie es aussehen wird.';
$string['cachedef_sessions'] = 'Cache für KI-Tutor Chat-Sitzungen';
$string['char'] = 'Zeichen';
$string['chars'] = 'Zeichen';
$string['choose_files'] = 'Dateien auswählen';
$string['clear_selection'] = 'Auswahl löschen';
$string['close'] = 'KI-Tutor schließen';
$string['configuration_error'] = 'Konfigurationsfehler';
$string['configure_now'] = 'Jetzt konfigurieren';
$string['connection_interrupted'] = '[Verbindung unterbrochen]';
$string['course_custom_prompt'] = 'Kursspezifischer benutzerdefinierter Prompt';
$string['course_custom_prompt_help'] = 'Dieser benutzerdefinierte Prompt überschreibt die globale Einstellung nur für diesen Kurs. Leer lassen, um den globalen Prompt zu verwenden.';
$string['course_materials'] = 'Kursmaterialien (PDFs)';
$string['course_materials_help'] = 'Laden Sie zusätzliche PDF-Dateien hoch, auf die sich der KI-Tutor bei der Beantwortung von Fragen beziehen soll.';
$string['custom_prompt'] = 'Benutzerdefinierter Prompt';
$string['custom_prompt_desc'] = 'Benutzerdefinierte Anweisungen zur Steuerung des Verhaltens des KI-Tutors. Verwenden Sie dieses Feld, um spezifische Richtlinien, Tonfall oder Wissensgrenzen für den Tutor bereitzustellen.';
$string['customavatar'] = 'Benutzerdefinierter Avatar';
$string['customavatar_desc'] = 'Laden Sie Ihr eigenes Benutzerdefiniertes Avatar-Bild hoch. Dies überschreibt den ausgewählten vordefinierten Avatar.';
$string['customavatar_dimensions'] = 'Empfohlene Abmessungen: 200x200 Pixel. Unterstützte Formate: PNG, JPG, JPEG, SVG. Maximale Dateigröße: 512KB.';
$string['drag_drop_upload'] = 'PDF-Dateien hierher ziehen und ablegen';
$string['drag_drop_upload_or_browse'] = 'oder klicken zum Durchsuchen';
$string['drawer_side'] = 'Schubladen-Öffnungsseite';
$string['drawer_side_help'] = 'Wählen Sie, von welcher Seite sich die Chat-Schublade öffnen soll. Dies ist unabhängig von der Position der Avatar-Schaltfläche.';
$string['drawer_side_left'] = 'Von links öffnen';
$string['drawer_side_right'] = 'Von rechts öffnen';
$string['dttutor:use'] = 'KI-Tutor nutzen';
$string['enable_tutor_for_course'] = 'KI-Tutor für diesen Kurs aktivieren';
$string['enable_tutor_for_course_help'] = 'Wenn aktiviert, steht der KI-Tutor Studierenden und Lehrenden in diesem Kurs zur Verfügung. Die globale Plugin-Einstellung muss ebenfalls aktiviert sein.';
$string['enabled'] = 'Chat aktivieren';
$string['enabled_desc'] = 'Aktivieren oder deaktivieren Sie den KI-Tutor Chat global';
$string['error_api_not_configured'] = 'API-Konfiguration fehlt. Bitte überprüfen Sie Ihre Einstellungen.';
$string['error_api_request_failed'] = 'API-Anfragefehler: {$a}';
$string['error_attempt_later'] = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später noch einmal.';
$string['error_cache_unavailable'] = 'Der Chat-Dienst ist vorübergehend nicht verfügbar. Bitte versuchen Sie, die Seite neu zu laden.';
$string['error_empty_message'] = 'Nachricht darf nicht leer sein';
$string['error_establish_sse_connection'] = '[Fehler] SSE-Verbindung konnte nicht hergestellt werden';
$string['error_http_code'] = 'HTTP-Fehler {$a}';
$string['error_insufficient_tokens'] = 'Es sind nicht genügend KI-Guthaben verfügbar, um Ihre Anfrage zu bearbeiten. Bitte wenden Sie sich an Ihren Administrator, um mehr Guthaben hinzuzufügen.';
$string['error_insufficient_tokens_short'] = 'Unzureichendes Guthaben';
$string['error_internal'] = 'Interner Fehler: {$a}';
$string['error_invalid_api_response'] = 'Ungültige API-Antwort';
$string['error_invalid_coordinates'] = 'Ungültige Koordinaten. Bitte verwenden Sie gültige CSS-Werte (z.B. 10px, 2rem, 50%)';
$string['error_invalid_message'] = 'Bitte geben Sie eine gültige Nachricht ein';
$string['error_invalid_position'] = 'Ungültige Positionsdaten';
$string['error_license_fallback'] = 'Lizenzfehler: {$a}';
$string['error_license_fallback_short'] = 'Lizenzfehler';
$string['error_license_not_allowed'] = 'Ihre Lizenz erlaubt keinen Zugriff auf den KI-Tutor-Dienst. Bitte kontaktieren Sie Ihren Administrator, um Ihren Lizenzstatus zu überprüfen oder Ihren Plan zu aktualisieren.';
$string['error_license_not_allowed_short'] = 'Lizenzfehler';
$string['error_message_too_long'] = '[Fehler] Nachricht ist zu lang. Maximal 4000 Zeichen.';
$string['error_metadata_too_large'] = 'Die mit Ihrer Nachricht gesendeten Metadaten sind zu groß. Bitte versuchen Sie es erneut.';
$string['error_no_credits'] = 'Nicht genügend KI-Guthaben verfügbar.';
$string['error_no_credits_fallback'] = 'Unzureichendes Guthaben: {$a}';
$string['error_no_credits_short'] = 'Kein Guthaben verfügbar';
$string['error_selected_text_too_large'] = 'Der ausgewählte Text ist zu groß. Bitte wählen Sie einen kleineren Abschnitt.';
$string['error_unexpected'] = 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
$string['error_unknown'] = 'Ein unbekannter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
$string['error_webservice_not_configured'] = 'Der KI-Tutor Chat ist nicht richtig konfiguriert und derzeit nicht verfügbar.';
$string['error_webservice_not_configured_action'] = 'Bitte kontaktieren Sie Ihren Administrator oder melden Sie dieses Problem, um den Chat-Dienst zu aktivieren.';
$string['error_webservice_not_configured_admin'] = 'Der Datacurso KI-Provider Webservice muss konfiguriert werden, bevor der KI-Tutor verwendet werden kann. <a href="{$a}" target="_blank">Klicken Sie hier, um ihn jetzt zu konfigurieren</a>.';
$string['error_webservice_not_configured_admin_inline'] = 'Der Datacurso KI-Provider Webservice muss konfiguriert werden, bevor der KI-Tutor verwendet werden kann.';
$string['error_webservice_not_configured_short'] = 'Chat-Dienst nicht verfügbar';
$string['last_indexed'] = 'Zuletzt synchronisiert: {$a}';
$string['line'] = 'Zeile';
$string['lines'] = 'Zeilen';
$string['loading'] = 'Laden...';
$string['manage_tutor'] = 'KI-Tutor Verwaltung';
$string['material_deleted'] = 'Material erfolgreich gelöscht';
$string['material_uploaded'] = 'Material erfolgreich hochgeladen';
$string['off_topic_detection_enabled'] = 'Off-Topic Erkennung aktivieren';
$string['off_topic_detection_enabled_desc'] = 'Wenn aktiviert, erkennt und reagiert der KI-Tutor auf themenfremde Nachrichten gemäß der unten konfigurierten Stufe.';
$string['off_topic_strictness'] = 'Off-Topic Strenge';
$string['off_topic_strictness_desc'] = 'Steuern Sie, wie streng die Off-Topic Erkennung ist. Permissiv erlaubt mehr Flexibilität, während Strikt nur kursbezogene Konversationen zulässt.';
$string['off_topic_strictness_moderate'] = 'Moderat';
$string['off_topic_strictness_permissive'] = 'Permissiv';
$string['off_topic_strictness_strict'] = 'Strikt';
$string['open'] = 'KI-Tutor öffnen';
$string['or_click_to_browse'] = 'oder klicken zum Durchsuchen';
$string['pluginname'] = 'KI-Tutor';
$string['position_custom'] = 'Benutzerdefinierte Position';
$string['position_left'] = 'Untere linke Ecke';
$string['position_preset'] = 'Voreingestellte Position';
$string['position_right'] = 'Untere rechte Ecke';
$string['position_x'] = 'Horizontale Position (X)';
$string['position_x_help'] = 'Abstand vom linken Rand. Beispiele: 2rem, 20px, 5%. Verwenden Sie negative Werte, um vom rechten Rand zu positionieren.';
$string['position_x_label'] = 'X: {$a->value} (von {$a->ref})';
$string['position_y'] = 'Vertikale Position (Y)';
$string['position_y_help'] = 'Abstand vom unteren Rand. Beispiele: 6rem, 80px, 10%. Verwenden Sie negative Werte, um vom oberen Rand zu positionieren.';
$string['position_y_label'] = 'Y: {$a->value} (von {$a->ref})';
$string['positiondisplay_corner'] = 'Position: {$a->preset} Ecke | Schublade: {$a->drawer}';
$string['positiondisplay_custom'] = 'Position: X: {$a->x}, Y: {$a->y} | Schublade: {$a->drawer}';
$string['preview'] = 'Live-Vorschau';
$string['ref_bottom'] = 'Unten';
$string['ref_left'] = 'Links';
$string['ref_right'] = 'Rechts';
$string['ref_top'] = 'Oben';
$string['reference_edge_x'] = 'Horizontale Referenzkante';
$string['reference_edge_y'] = 'Vertikale Referenzkante';
$string['selected'] = 'ausgewählt';
$string['selection_indicator'] = '{$a} Zeilen ausgewählt';
$string['selectionformat'] = '{$a->lines} {$a->linetext}, {$a->chars} {$a->chartext} ausgewählt';
$string['sendmessage'] = 'Nachricht senden';
$string['sessionnotready'] = 'Die KI-Tutor Sitzung ist nicht bereit. Bitte versuchen Sie es erneut.';
$string['student'] = 'Student';
$string['teacher'] = 'Lehrer';
$string['tutor_disabled_notice'] = 'Der KI-Tutor ist derzeit für diesen Kurs deaktiviert. Studierende sehen die Chat-Oberfläche nicht.';
$string['tutor_status'] = 'KI-Tutor Status';
$string['tutorcustomization'] = 'Tutor Anpassung';
$string['tutorname_default'] = 'KI-Tutor';
$string['tutorname_setting'] = 'Tutor Name';
$string['tutorname_setting_desc'] = 'Konfigurieren Sie den Namen, der im Chat-Header angezeigt werden soll. Sie können {teachername} verwenden, um den tatsächlichen Namen des Lehrers anzuzeigen, oder einen benutzerdefinierten Namen eingeben. Beispiele: "{teachername}" zeigt "Max Mustermann", "KI-Assistent" zeigt "KI-Assistent".';
$string['typemessage'] = 'Geben Sie Ihre Nachricht ein...';
$string['unauthorized'] = 'Unbefugter Zugriff';
$string['upload_files'] = 'Dateien hochladen';
$string['upload_material'] = 'PDF hochladen';
$string['welcomemessage'] = 'Hallo! Ich bin dein KI-Assistent. Wie kann ich dir heute helfen?';
$string['welcomemessage_default'] = 'Hallo! Ich bin {teachername}, dein KI-Assistent. Wie kann ich dir heute helfen?';
$string['welcomemessage_setting'] = 'Willkommensnachricht';
$string['welcomemessage_setting_desc'] = 'Passen Sie die Willkommensnachricht an, die beim Öffnen des Chats angezeigt wird. Sie können Platzhalter verwenden: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Gestern';
