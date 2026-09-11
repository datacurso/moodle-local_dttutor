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
$string['cachedef_sessions'] = 'Cache für die Chat-Sitzungskennungen des KI-Tutors';
$string['char'] = 'Zeichen';
$string['chars'] = 'Zeichen';
$string['clear_selection'] = 'Auswahl löschen';
$string['close'] = 'KI-Tutor schließen';
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
$string['enable_tutor_for_course'] = 'KI-Tutor für diesen Kurs aktivieren';
$string['enable_tutor_for_course_help'] = 'Wenn aktiviert, steht der KI-Tutor Studierenden und Lehrenden in diesem Kurs zur Verfügung. Die globale Plugin-Einstellung muss ebenfalls aktiviert sein.';
$string['enabled'] = 'Chat aktivieren';
$string['enabled_desc'] = 'Aktivieren oder deaktivieren Sie den KI-Tutor Chat global';
$string['error_api_not_configured'] = 'API-Konfiguration fehlt. Bitte überprüfen Sie Ihre Einstellungen.';
$string['error_attempt_later'] = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später noch einmal.';
$string['error_history_unavailable'] = 'Die vorherige Unterhaltung konnte nicht geladen werden. Sie können weiter chatten.';
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
$string['error_no_credits_fallback'] = 'Unzureichendes Guthaben: {$a}';
$string['error_ratelimit_exceeded'] = 'Das zulässige Nutzungslimit wurde überschritten. Bitte versuchen Sie es am {$a} erneut.';
$string['error_tutor_not_available'] = 'Der KI-Tutor ist für diesen Kurs nicht verfügbar.';
$string['error_unexpected'] = 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
$string['error_unknown'] = 'Ein unbekannter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
$string['include_grades'] = 'Bewertungen der Teilnehmer/innen an den KI-Tutor senden';
$string['include_grades_desc'] = 'Wenn aktiviert, werden die eigenen Kursbewertungen der aktuellen Teilnehmerin bzw. des aktuellen Teilnehmers dem an den Datacurso-AI-Dienst gesendeten Kontext hinzugefügt, damit der Tutor Fragen dazu beantworten kann. Standardmäßig deaktiviert, um die übertragenen personenbezogenen Daten zu minimieren.';
$string['line'] = 'Zeile';
$string['lines'] = 'Zeilen';
$string['loading'] = 'Laden...';
$string['manage_tutor'] = 'KI-Tutor Verwaltung';
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
$string['privacy:export:course_config'] = 'Tutor-Konfiguration des Kurses';
$string['privacy:export:sessions'] = 'Chat-Sitzungen';
$string['privacy:metadata:datacurso_ai'] = 'Chat-Nachrichten und Kurskontext werden an den Datacurso-AI-Dienst gesendet, um die Antworten des Tutors zu erzeugen.';
$string['privacy:metadata:datacurso_ai:cmid'] = 'Die ID des Kursmoduls, das die Person beim Schreiben der Nachricht angesehen hat.';
$string['privacy:metadata:datacurso_ai:course_structure'] = 'Die für die Person sichtbare Kursstruktur (Aktivitäten, Abschnitte, Termine und Höchstbewertungen).';
$string['privacy:metadata:datacurso_ai:custom_prompt'] = 'Die vom Administrator konfigurierten institutionellen Anweisungen.';
$string['privacy:metadata:datacurso_ai:grades'] = 'Die eigenen Kursbewertungen der Person, nur wenn die Einstellung "Bewertungen der Teilnehmer/innen senden" aktiviert ist.';
$string['privacy:metadata:datacurso_ai:lang'] = 'Die aktuelle Sprache der Person.';
$string['privacy:metadata:datacurso_ai:messages'] = 'Die von der Person geschriebenen Chat-Nachrichten und die vorherigen Antworten des Tutors.';
$string['privacy:metadata:datacurso_ai:page_url'] = 'Die URL der Moodle-Seite, von der die Nachricht gesendet wurde.';
$string['privacy:metadata:datacurso_ai:selected_text'] = 'Auf der Seite markierter Text, zu dem die Person eine Frage stellt.';
$string['privacy:metadata:datacurso_ai:site_id'] = 'Die anonyme Kennung dieser Moodle-Site.';
$string['privacy:metadata:datacurso_ai:site_url'] = 'Die URL dieser Moodle-Site.';
$string['privacy:metadata:datacurso_ai:timezone'] = 'Die Zeitzone der Person.';
$string['privacy:metadata:datacurso_ai:userid'] = 'Die ID der Person, die die Nachricht sendet.';
$string['privacy:metadata:local_dttutor_course_config'] = 'Kursweise Konfiguration des KI-Tutors.';
$string['privacy:metadata:local_dttutor_course_config:timemodified'] = 'Zeitpunkt der letzten Änderung der Konfiguration.';
$string['privacy:metadata:local_dttutor_course_config:usermodified'] = 'Die ID der Person, die die Konfiguration zuletzt geändert hat.';
$string['privacy:metadata:local_dttutor_session'] = 'Kennungen der Chat-Sitzungen, die eine Person mit dem Datacurso-AI-Tutor eröffnet hat, gespeichert, damit sie remote gelöscht werden können.';
$string['privacy:metadata:local_dttutor_session:cmid'] = 'Die ID des Kursmoduls, zu dem die Sitzung gehört (0 für den gesamten Kurs).';
$string['privacy:metadata:local_dttutor_session:courseid'] = 'Die ID des Kurses, zu dem die Sitzung gehört.';
$string['privacy:metadata:local_dttutor_session:remotesessionid'] = 'Die Kennung der Sitzung im Datacurso-AI-Dienst.';
$string['privacy:metadata:local_dttutor_session:timecreated'] = 'Zeitpunkt der Erstellung der Sitzung.';
$string['privacy:metadata:local_dttutor_session:timemodified'] = 'Zeitpunkt der letzten Erneuerung der Sitzung.';
$string['privacy:metadata:local_dttutor_session:userid'] = 'Die ID der Person, der die Sitzung gehört.';
$string['ref_bottom'] = 'Unten';
$string['ref_left'] = 'Links';
$string['ref_right'] = 'Rechts';
$string['ref_top'] = 'Oben';
$string['reference_edge_x'] = 'Horizontale Referenzkante';
$string['reference_edge_y'] = 'Vertikale Referenzkante';
$string['selected'] = 'ausgewählt';
$string['sendmessage'] = 'Nachricht senden';
$string['student'] = 'Student';
$string['teacher'] = 'Lehrer';
$string['tutor_disabled_notice'] = 'Der KI-Tutor ist derzeit für diesen Kurs deaktiviert. Studierende sehen die Chat-Oberfläche nicht.';
$string['tutor_status'] = 'KI-Tutor Status';
$string['tutorcustomization'] = 'Tutor Anpassung';
$string['tutorname_default'] = 'KI-Tutor';
$string['tutorname_setting'] = 'Tutor Name';
$string['tutorname_setting_desc'] = 'Konfigurieren Sie den Namen, der im Chat-Header angezeigt werden soll. Sie können {teachername} verwenden, um den tatsächlichen Namen des Lehrers anzuzeigen, oder einen benutzerdefinierten Namen eingeben. Beispiele: "{teachername}" zeigt "Max Mustermann", "KI-Assistent" zeigt "KI-Assistent".';
$string['typemessage'] = 'Geben Sie Ihre Nachricht ein...';
$string['welcomemessage_default'] = 'Hallo! Ich bin {teachername}, dein KI-Assistent. Wie kann ich dir heute helfen?';
$string['welcomemessage_setting'] = 'Willkommensnachricht';
$string['welcomemessage_setting_desc'] = 'Passen Sie die Willkommensnachricht an, die beim Öffnen des Chats angezeigt wird. Sie können Platzhalter verwenden: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Gestern';
