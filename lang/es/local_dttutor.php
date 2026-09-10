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
 * Spanish language strings for Tutor-IA plugin.
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['avatar'] = 'Avatar del Tutor IA';
$string['avatar_desc'] = 'Seleccione el avatar que se mostrará en el botón de chat flotante del Tutor IA. Si no se selecciona ninguno o el archivo no existe, se utilizará el Avatar 1 por defecto.';
$string['avatar_position'] = 'Posición del avatar';
$string['avatar_position_desc'] = 'Configure dónde se mostrará el botón flotante del avatar del Tutor IA. Elija una posición predefinida en una esquina o personalice las coordenadas X,Y exactas. La vista previa en vivo muestra cómo aparecerá.';
$string['cachedef_course_knowledge'] = 'Caché del conocimiento del curso precargado utilizado por el chat';
$string['cachedef_sessions'] = 'Caché de los identificadores de sesión del chat del Tutor IA';
$string['char'] = 'carácter';
$string['chars'] = 'caracteres';
$string['clear_selection'] = 'Borrar selección';
$string['close'] = 'Cerrar Tutor IA';
$string['ctx_loc_activity'] = 'Ubicación: Actividad';
$string['ctx_loc_admin'] = 'Ubicación: Administración';
$string['ctx_loc_calendar'] = 'Ubicación: Calendario';
$string['ctx_loc_course'] = 'Ubicación: Curso';
$string['ctx_loc_dashboard'] = 'Ubicación: Tablero';
$string['ctx_loc_files'] = 'Ubicación: Archivos';
$string['ctx_loc_gradebook'] = 'Ubicación: Calificaciones';
$string['ctx_loc_messages'] = 'Ubicación: Mensajes';
$string['ctx_loc_profile'] = 'Ubicación: Perfil';
$string['custom_prompt'] = 'Prompt personalizado';
$string['custom_prompt_desc'] = 'Instrucciones personalizadas para controlar el comportamiento del tutor IA. Utilice este campo para proporcionar directrices específicas, tono o límites de conocimiento para el tutor.';
$string['customavatar'] = 'Avatar personalizado';
$string['customavatar_desc'] = 'Suba su propia imagen de avatar personalizada. Esto anulará el avatar predefinido seleccionado.';
$string['customavatar_dimensions'] = 'Dimensiones recomendadas: 200x200 píxeles. Formatos soportados: PNG, JPG, JPEG, SVG. Tamaño máximo de archivo: 512KB.';
$string['drawer_side'] = 'Lado de apertura del cajón';
$string['drawer_side_help'] = 'Elija desde qué lado se abrirá el cajón de chat. Esto es independiente de la posición del botón de avatar.';
$string['drawer_side_left'] = 'Abrir desde la izquierda';
$string['drawer_side_right'] = 'Abrir desde la derecha';
$string['dttutor:use'] = 'Usar Tutor IA';
$string['edit_cancel'] = 'Cancelar';
$string['edit_message'] = 'Editar mensaje';
$string['edit_save'] = 'Guardar';
$string['enable_tutor_for_course'] = 'Habilitar Tutor IA para este curso';
$string['enable_tutor_for_course_help'] = 'Cuando está habilitado, el Tutor IA estará disponible para estudiantes y profesores en este curso. La configuración global del plugin también debe estar habilitada.';
$string['enabled'] = 'Habilitar Chat';
$string['enabled_desc'] = 'Habilitar o deshabilitar el chat del Tutor IA globalmente';
$string['error_api_not_configured'] = 'Falta la configuración de la API. Por favor, revise su configuración.';
$string['error_attempt_later'] = 'Ocurrió un error. Por favor, inténtelo de nuevo más tarde.';
$string['error_history_unavailable'] = 'No se pudo cargar la conversación anterior. Puede seguir conversando.';
$string['error_insufficient_tokens'] = 'No hay suficientes créditos de IA disponibles para procesar su solicitud. Por favor, contacte a su administrador para añadir más créditos y continuar usando el Tutor IA.';
$string['error_insufficient_tokens_short'] = 'Créditos insuficientes';
$string['error_internal'] = 'Error interno: {$a}';
$string['error_invalid_coordinates'] = 'Coordenadas inválidas. Por favor use valores CSS válidos (ej., 10px, 2rem, 50%)';
$string['error_invalid_message'] = 'Por favor introduzca un mensaje válido';
$string['error_invalid_position'] = 'Datos de posición inválidos';
$string['error_license_fallback'] = 'Error de licencia: {$a}';
$string['error_license_fallback_short'] = 'Error de licencia';
$string['error_license_not_allowed'] = 'Su licencia no permite el acceso al servicio Tutor IA. Por favor, contacte a su administrador para verificar el estado de su licencia o actualizar su plan.';
$string['error_license_not_allowed_short'] = 'Error de licencia';
$string['error_message_too_long'] = '[Error] El mensaje es demasiado largo. Máximo 4000 caracteres.';
$string['error_no_credits_fallback'] = 'Créditos insuficientes: {$a}';
$string['error_tutor_not_available'] = 'El Tutor IA no está disponible para este curso.';
$string['error_unexpected'] = 'Ocurrió un error inesperado. Por favor, inténtelo de nuevo.';
$string['error_unknown'] = 'Ocurrió un error desconocido. Por favor, inténtelo de nuevo.';
$string['include_grades'] = 'Enviar las calificaciones del estudiante al tutor IA';
$string['include_grades_desc'] = 'Cuando está habilitado, las calificaciones del propio estudiante en el curso se añaden al contexto enviado al servicio Datacurso AI para que el tutor pueda responder preguntas sobre ellas. Deshabilitado por defecto para minimizar los datos personales transferidos.';
$string['line'] = 'línea';
$string['lines'] = 'líneas';
$string['loading'] = 'Cargando...';
$string['manage_tutor'] = 'Gestión del Tutor IA';
$string['open'] = 'Abrir Tutor IA';
$string['pluginname'] = 'Tutor IA';
$string['position_custom'] = 'Posición personalizada';
$string['position_left'] = 'Esquina inferior izquierda';
$string['position_preset'] = 'Posición predefinida';
$string['position_right'] = 'Esquina inferior derecha';
$string['position_x'] = 'Posición horizontal (X)';
$string['position_x_help'] = 'Distancia desde el borde izquierdo. Ejemplos: 2rem, 20px, 5%. Use valores negativos para posicionar desde el borde derecho.';
$string['position_y'] = 'Posición vertical (Y)';
$string['position_y_help'] = 'Distancia desde el borde inferior. Ejemplos: 6rem, 80px, 10%. Use valores negativos para posicionar desde el borde superior.';
$string['positiondisplay_corner'] = 'Posición: esquina {$a->preset} | Cajón: {$a->drawer}';
$string['positiondisplay_custom'] = 'Posición: X: {$a->x}, Y: {$a->y} | Cajón: {$a->drawer}';
$string['preview'] = 'Vista previa en vivo';
$string['privacy:export:course_config'] = 'Configuración del tutor en el curso';
$string['privacy:export:sessions'] = 'Sesiones de chat';
$string['privacy:metadata:datacurso_ai'] = 'Los mensajes del chat y el contexto del curso se envían al servicio Datacurso AI para generar las respuestas del tutor.';
$string['privacy:metadata:datacurso_ai:cmid'] = 'El ID del módulo del curso que el usuario estaba viendo al escribir el mensaje.';
$string['privacy:metadata:datacurso_ai:course_structure'] = 'La estructura del curso visible para el usuario (actividades, secciones, fechas y calificaciones máximas).';
$string['privacy:metadata:datacurso_ai:custom_prompt'] = 'Las instrucciones personalizadas institucionales configuradas por el administrador.';
$string['privacy:metadata:datacurso_ai:grades'] = 'Las calificaciones del propio usuario en el curso, solo cuando el ajuste "Enviar las calificaciones del estudiante" está habilitado.';
$string['privacy:metadata:datacurso_ai:lang'] = 'El idioma actual del usuario.';
$string['privacy:metadata:datacurso_ai:messages'] = 'Los mensajes del chat escritos por el usuario y las respuestas anteriores del tutor.';
$string['privacy:metadata:datacurso_ai:page_url'] = 'La URL de la página de Moodle desde la que se envió el mensaje.';
$string['privacy:metadata:datacurso_ai:selected_text'] = 'El texto que el usuario seleccionó en la página para preguntar sobre él.';
$string['privacy:metadata:datacurso_ai:site_id'] = 'El identificador anónimo de este sitio Moodle.';
$string['privacy:metadata:datacurso_ai:site_url'] = 'La URL de este sitio Moodle.';
$string['privacy:metadata:datacurso_ai:timezone'] = 'La zona horaria del usuario.';
$string['privacy:metadata:datacurso_ai:userid'] = 'El ID del usuario que envía el mensaje.';
$string['privacy:metadata:local_dttutor_course_config'] = 'Configuración del tutor IA por curso.';
$string['privacy:metadata:local_dttutor_course_config:timemodified'] = 'Cuándo se modificó la configuración por última vez.';
$string['privacy:metadata:local_dttutor_course_config:usermodified'] = 'El ID del usuario que modificó la configuración por última vez.';
$string['privacy:metadata:local_dttutor_session'] = 'Identificadores de las sesiones de chat que un usuario abrió con el tutor Datacurso AI, almacenados para poder eliminarlas de forma remota.';
$string['privacy:metadata:local_dttutor_session:cmid'] = 'El ID del módulo del curso al que pertenece la sesión (0 para todo el curso).';
$string['privacy:metadata:local_dttutor_session:courseid'] = 'El ID del curso al que pertenece la sesión.';
$string['privacy:metadata:local_dttutor_session:remotesessionid'] = 'El identificador de la sesión en el servicio Datacurso AI.';
$string['privacy:metadata:local_dttutor_session:timecreated'] = 'Cuándo se creó la sesión.';
$string['privacy:metadata:local_dttutor_session:timemodified'] = 'Cuándo se renovó la sesión por última vez.';
$string['privacy:metadata:local_dttutor_session:userid'] = 'El ID del usuario propietario de la sesión.';
$string['ref_bottom'] = 'Abajo';
$string['ref_left'] = 'Izquierda';
$string['ref_right'] = 'Derecha';
$string['ref_top'] = 'Arriba';
$string['reference_edge_x'] = 'Borde de referencia horizontal';
$string['reference_edge_y'] = 'Borde de referencia vertical';
$string['selected'] = 'seleccionado';
$string['sendmessage'] = 'Enviar mensaje';
$string['student'] = 'Estudiante';
$string['teacher'] = 'Profesor';
$string['tutor_disabled_notice'] = 'El Tutor IA está actualmente deshabilitado para este curso. Los estudiantes no verán la interfaz de chat.';
$string['tutor_status'] = 'Estado del Tutor IA';
$string['tutorcustomization'] = 'Personalización del Tutor';
$string['tutorname_default'] = 'Tutor IA';
$string['tutorname_setting'] = 'Nombre del tutor';
$string['tutorname_setting_desc'] = 'Configure el nombre que se mostrará en el encabezado del chat. Puede usar {teachername} para mostrar el nombre real del profesor del curso, o introducir un nombre personalizado. Ejemplos: "{teachername}" mostrará "Juan Pérez", "Asistente IA" mostrará "Asistente IA".';
$string['typemessage'] = 'Escriba su mensaje...';
$string['welcomemessage_default'] = '¡Hola! Soy {teachername}, tu asistente de IA. ¿Cómo puedo ayudarte hoy?';
$string['welcomemessage_setting'] = 'Mensaje de bienvenida';
$string['welcomemessage_setting_desc'] = 'Personalice el mensaje de bienvenida que se muestra cuando se abre el chat. Puede usar marcadores de posición: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Ayer';
