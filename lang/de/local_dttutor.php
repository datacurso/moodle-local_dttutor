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

$string['avatar'] = 'KI-Tutor Avatar';
$string['avatar_desc'] = 'Wählen Sie den Avatar, der auf der schwebenden Chat-Schaltfläche des KI-Tutors angezeigt werden soll. Wenn keiner ausgewählt ist oder die Datei nicht existiert, wird standardmäßig Avatar 1 verwendet.';
$string['avatar_position'] = 'Avatar-Position';
$string['avatar_position_desc'] = 'Konfigurieren Sie, wo die schwebende Avatar-Schaltfläche angezeigt werden soll. Wählen Sie eine voreingestellte Eckposition oder passen Sie die genauen X,Y-Koordinaten an. Die Live-Vorschau zeigt, wie es aussehen wird.';
$string['cachedef_course_knowledge'] = 'Cache für vorgeladenes Kurswissen, das vom Chat verwendet wird';
$string['cachedef_schema_cache'] = 'Cache für Schemata von Moodle-Webservice-Funktionen';
$string['char'] = 'Zeichen';
$string['chars'] = 'Zeichen';
$string['clear_selection'] = 'Auswahl löschen';
$string['close'] = 'KI-Tutor schließen';
$string['configuration_error'] = 'Konfigurationsfehler';
$string['connection_interrupted'] = '[Verbindung unterbrochen]';
$string['course_materials'] = 'Kursmaterialien (PDFs)';
$string['ctx_loc_activity'] = 'Ort: Aktivität';
$string['ctx_loc_admin'] = 'Ort: Administration';
$string['ctx_loc_calendar'] = 'Ort: Kalender';
$string['ctx_loc_course'] = 'Ort: Kurs';
$string['ctx_loc_dashboard'] = 'Ort: Dashboard';
$string['ctx_loc_files'] = 'Ort: Dateien';
$string['ctx_loc_gradebook'] = 'Ort: Bewertungen';
$string['ctx_loc_messages'] = 'Ort: Mitteilungen';
$string['ctx_loc_profile'] = 'Ort: Profil';
$string['custom_prompt'] = 'Benutzerdefinierter Prompt';
$string['custom_prompt_desc'] = 'Benutzerdefinierte Anweisungen zur Steuerung des Verhaltens des KI-Tutors. Verwenden Sie dieses Feld, um spezifische Richtlinien, Tonfall oder Wissensgrenzen für den Tutor bereitzustellen.';
$string['customavatar'] = 'Benutzerdefinierter Avatar';
$string['customavatar_desc'] = 'Laden Sie Ihr eigenes Benutzerdefiniertes Avatar-Bild hoch. Dies überschreibt den ausgewählten vordefinierten Avatar.';
$string['customavatar_dimensions'] = 'Empfohlene Abmessungen: 200x200 Pixel. Unterstützte Formate: PNG, JPG, JPEG, SVG. Maximale Dateigröße: 512KB.';
$string['drawer_side'] = 'Schubladen-Öffnungsseite';
$string['drawer_side_help'] = 'Wählen Sie, von welcher Seite sich die Chat-Schublade öffnen soll. Dies ist unabhängig von der Position der Avatar-Schaltfläche.';
$string['drawer_side_left'] = 'Von links öffnen';
$string['drawer_side_right'] = 'Von rechts öffnen';
$string['dttutor:use'] = 'KI-Tutor nutzen';
$string['edit_cancel'] = 'Abbrechen';
$string['edit_message'] = 'Nachricht bearbeiten';
$string['edit_save'] = 'Speichern';
$string['editing_message'] = 'Nachricht wird bearbeitet';
$string['enable_tutor_for_course'] = 'KI-Tutor für diesen Kurs aktivieren';
$string['enable_tutor_for_course_help'] = 'Wenn aktiviert, steht der KI-Tutor Studierenden und Lehrenden in diesem Kurs zur Verfügung. Die globale Plugin-Einstellung muss ebenfalls aktiviert sein.';
$string['enabled'] = 'Chat aktivieren';
$string['enabled_desc'] = 'Aktivieren oder deaktivieren Sie den KI-Tutor Chat global';
$string['error_api_not_configured'] = 'API-Konfiguration fehlt. Bitte überprüfen Sie Ihre Einstellungen.';
$string['error_attempt_later'] = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später noch einmal.';
$string['error_empty_message'] = 'Nachricht darf nicht leer sein';
$string['error_establish_sse_connection'] = '[Fehler] SSE-Verbindung konnte nicht hergestellt werden';
$string['error_insufficient_tokens'] = 'Es sind nicht genügend KI-Guthaben verfügbar, um Ihre Anfrage zu bearbeiten. Bitte wenden Sie sich an Ihren Administrator, um mehr Guthaben hinzuzufügen.';
$string['error_insufficient_tokens_short'] = 'Unzureichendes Guthaben';
$string['error_internal'] = 'Interner Fehler: {$a}';
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
$string['pluginname'] = 'KI-Tutor';
$string['position_custom'] = 'Benutzerdefinierte Position';
$string['position_left'] = 'Untere linke Ecke';
$string['position_preset'] = 'Voreingestellte Position';
$string['position_right'] = 'Untere rechte Ecke';
$string['position_x'] = 'Horizontale Position (X)';
$string['position_x_help'] = 'Abstand vom linken Rand. Beispiele: 2rem, 20px, 5%. Verwenden Sie negative Werte, um vom rechten Rand zu positionieren.';
$string['position_y'] = 'Vertikale Position (Y)';
$string['position_y_help'] = 'Abstand vom unteren Rand. Beispiele: 6rem, 80px, 10%. Verwenden Sie negative Werte, um vom oberen Rand zu positionieren.';
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
$string['sendmessage'] = 'Nachricht senden';
$string['servicebot_firstname'] = 'Tutor';
$string['servicebot_lastname'] = 'KI';
$string['sessionnotready'] = 'Die KI-Tutor Sitzung ist nicht bereit. Bitte versuchen Sie es erneut.';
$string['student'] = 'Student';
$string['teacher'] = 'Lehrer';
$string['tool_call_webservice_desc'] = 'Ruft eine Moodle-Webservice-Funktion auf. Die Funktion funktioniert nur, wenn der Benutzer über die erforderlichen Berechtigungen in Moodle verfügt.';
$string['tool_call_webservice_function'] = 'Der Name der Webservice-Funktion (z. B. core_user_get_users, core_course_get_courses).';
$string['tool_call_webservice_params'] = 'Parameter, die der Funktion als Schlüssel-Wert-Paare übergeben werden.';
$string['tool_ws_describe_desc'] = 'Ruft die vollständige Dokumentation einer bestimmten Moodle-Webservice-Funktion ab: Beschreibung, Parameter mit Typen und Rückgabestruktur.';
$string['tool_ws_describe_wsname'] = 'Genauer Name der Webservice-Funktion (z. B. core_user_create_users, core_course_get_courses).';
$string['tool_ws_search_desc'] = 'Sucht Moodle-Webservice-Funktionen nach Absicht oder Schlüsselwörtern. Gibt Funktionsname, Komponente, Beschreibung, Parameter und Rückgabewerte zurück.';
$string['tool_ws_search_limit'] = 'Maximale Anzahl der Ergebnisse (Standard: 20, max.: 30).';
$string['tool_ws_search_query'] = 'Absicht oder Schlüsselwörter auf ENGLISCH (z. B. "create user", "enrol student", "get course grades").';
$string['tutor_disabled_notice'] = 'Der KI-Tutor ist derzeit für diesen Kurs deaktiviert. Studierende sehen die Chat-Oberfläche nicht.';
$string['tutor_status'] = 'KI-Tutor Status';
$string['tutorcustomization'] = 'Tutor Anpassung';
$string['tutorname_default'] = 'KI-Tutor';
$string['tutorname_setting'] = 'Tutor Name';
$string['tutorname_setting_desc'] = 'Konfigurieren Sie den Namen, der im Chat-Header angezeigt werden soll. Sie können {teachername} verwenden, um den tatsächlichen Namen des Lehrers anzuzeigen, oder einen benutzerdefinierten Namen eingeben. Beispiele: "{teachername}" zeigt "Max Mustermann", "KI-Assistent" zeigt "KI-Assistent".';
$string['typemessage'] = 'Geben Sie Ihre Nachricht ein...';
$string['welcomemessage'] = 'Hallo! Ich bin dein KI-Assistent. Wie kann ich dir heute helfen?';
$string['welcomemessage_default'] = 'Hallo! Ich bin {teachername}, dein KI-Assistent. Wie kann ich dir heute helfen?';
$string['welcomemessage_setting'] = 'Willkommensnachricht';
$string['welcomemessage_setting_desc'] = 'Passen Sie die Willkommensnachricht an, die beim Öffnen des Chats angezeigt wird. Sie können Platzhalter verwenden: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Gestern';
