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

$string['accepted_files_pdf_only'] = 'Solo se aceptan archivos PDF (máximo 50 archivos)';
$string['avatar'] = 'Avatar del Tutor IA';
$string['avatar_desc'] = 'Seleccione el avatar que se mostrará en el botón de chat flotante del Tutor IA. Si no se selecciona ninguno o el archivo no existe, se utilizará el Avatar 1 por defecto.';
$string['avatar_position'] = 'Posición del avatar';
$string['avatar_position_desc'] = 'Configure dónde se mostrará el botón flotante del avatar del Tutor IA. Elija una posición predefinida en una esquina o personalice las coordenadas X,Y exactas. La vista previa en vivo muestra cómo aparecerá.';
$string['cachedef_sessions'] = 'Caché para sesiones de chat de Tutor IA';
$string['char'] = 'carácter';
$string['chars'] = 'caracteres';
$string['choose_files'] = 'Elegir archivos';
$string['clear_selection'] = 'Borrar selección';
$string['close'] = 'Cerrar Tutor IA';
$string['configuration_error'] = 'Error de configuración';
$string['configure_now'] = 'Configurar ahora';
$string['connection_interrupted'] = '[Conexión interrumpida]';
$string['course_custom_prompt'] = 'Prompt personalizado específico del curso';
$string['course_custom_prompt_help'] = 'Este prompt personalizado anula la configuración global solo para este curso. Deje vacío para utilizar el prompt global.';
$string['course_materials'] = 'Materiales del curso (PDFs)';
$string['course_materials_help'] = 'Suba archivos PDF adicionales que el tutor IA debería consultar al responder preguntas.';
$string['custom_prompt'] = 'Prompt personalizado';
$string['custom_prompt_desc'] = 'Instrucciones personalizadas para controlar el comportamiento del tutor IA. Utilice este campo para proporcionar directrices específicas, tono o límites de conocimiento para el tutor.';
$string['customavatar'] = 'Avatar personalizado';
$string['customavatar_desc'] = 'Suba su propia imagen de avatar personalizada. Esto anulará el avatar predefinido seleccionado.';
$string['customavatar_dimensions'] = 'Dimensiones recomendadas: 200x200 píxeles. Formatos soportados: PNG, JPG, JPEG, SVG. Tamaño máximo de archivo: 512KB.';
$string['drag_drop_upload'] = 'Arrastre y suelte archivos PDF aquí';
$string['drag_drop_upload_or_browse'] = 'o haga clic para buscar';
$string['drawer_side'] = 'Lado de apertura del cajón';
$string['drawer_side_help'] = 'Elija desde qué lado se abrirá el cajón de chat. Esto es independiente de la posición del botón de avatar.';
$string['drawer_side_left'] = 'Abrir desde la izquierda';
$string['drawer_side_right'] = 'Abrir desde la derecha';
$string['dttutor:use'] = 'Usar Tutor IA';
$string['edit_cancel'] = 'Cancelar';
$string['edit_message'] = 'Editar mensaje';
$string['edit_save'] = 'Guardar';
$string['editing_message'] = 'Editando mensaje';
$string['enable_tutor_for_course'] = 'Habilitar Tutor IA para este curso';
$string['enable_tutor_for_course_help'] = 'Cuando está habilitado, el Tutor IA estará disponible para estudiantes y profesores en este curso. La configuración global del plugin también debe estar habilitada.';
$string['enabled'] = 'Habilitar Chat';
$string['enabled_desc'] = 'Habilitar o deshabilitar el chat del Tutor IA globalmente';
$string['error_api_not_configured'] = 'Falta la configuración de la API. Por favor, revise su configuración.';
$string['error_api_request_failed'] = 'Error de solicitud API: {$a}';
$string['error_attempt_later'] = 'Ocurrió un error. Por favor, inténtelo de nuevo más tarde.';
$string['error_cache_unavailable'] = 'El servicio de chat no está disponible temporalmente. Por favor, intente actualizar la página.';
$string['error_empty_message'] = 'El mensaje no puede estar vacío';
$string['error_establish_sse_connection'] = '[Error] No se pudo establecer conexión SSE';
$string['error_http_code'] = 'Error HTTP {$a}';
$string['error_insufficient_tokens'] = 'No hay suficientes créditos de IA disponibles para procesar su solicitud. Por favor, contacte a su administrador para añadir más créditos y continuar usando el Tutor IA.';
$string['error_insufficient_tokens_short'] = 'Créditos insuficientes';
$string['error_internal'] = 'Error interno: {$a}';
$string['error_invalid_api_response'] = 'Respuesta de API inválida';
$string['error_invalid_coordinates'] = 'Coordenadas inválidas. Por favor use valores CSS válidos (ej., 10px, 2rem, 50%)';
$string['error_invalid_message'] = 'Por favor introduzca un mensaje válido';
$string['error_invalid_position'] = 'Datos de posición inválidos';
$string['error_license_fallback'] = 'Error de licencia: {$a}';
$string['error_license_fallback_short'] = 'Error de licencia';
$string['error_license_not_allowed'] = 'Su licencia no permite el acceso al servicio Tutor IA. Por favor, contacte a su administrador para verificar el estado de su licencia o actualizar su plan.';
$string['error_license_not_allowed_short'] = 'Error de licencia';
$string['error_message_too_long'] = '[Error] El mensaje es demasiado largo. Máximo 4000 caracteres.';
$string['error_metadata_too_large'] = 'Los metadatos enviados con su mensaje son demasiado grandes. Por favor, inténtelo de nuevo.';
$string['error_no_credits'] = 'No hay suficientes créditos de IA disponibles.';
$string['error_no_credits_fallback'] = 'Créditos insuficientes: {$a}';
$string['error_no_credits_short'] = 'Sin créditos disponibles';
$string['error_selected_text_too_large'] = 'El texto seleccionado es demasiado grande. Por favor, seleccione una porción más pequeña.';
$string['error_unexpected'] = 'Ocurrió un error inesperado. Por favor, inténtelo de nuevo.';
$string['error_unknown'] = 'Ocurrió un error desconocido. Por favor, inténtelo de nuevo.';
$string['error_webservice_not_configured'] = 'El chat del Tutor IA no está configurado correctamente y no está disponible actualmente.';
$string['error_webservice_not_configured_action'] = 'Por favor, contacte al administrador del sitio o reporte este problema para activar el servicio de chat.';
$string['error_webservice_not_configured_admin'] = 'El servicio web del Proveedor de IA Datacurso necesita ser configurado antes de usar el Tutor IA. <a href="{$a}" target="_blank">Haga clic aquí para configurarlo ahora</a>.';
$string['error_webservice_not_configured_admin_inline'] = 'El servicio web del Proveedor de IA Datacurso necesita ser configurado antes de usar el Tutor IA.';
$string['error_webservice_not_configured_short'] = 'Servicio de chat no disponible';
$string['last_indexed'] = 'Última sincronización: {$a}';
$string['line'] = 'línea';
$string['lines'] = 'líneas';
$string['loading'] = 'Cargando...';
$string['manage_tutor'] = 'Gestión del Tutor IA';
$string['material_deleted'] = 'Material eliminado con éxito';
$string['material_uploaded'] = 'Material subido con éxito';
$string['off_topic_detection_enabled'] = 'Habilitar detección fuera de tema';
$string['off_topic_detection_enabled_desc'] = 'Cuando está habilitado, el tutor IA detectará y responderá a mensajes fuera de tema según el nivel de estrictez configurado abajo.';
$string['off_topic_strictness'] = 'Estrictez fuera de tema';
$string['off_topic_strictness_desc'] = 'Controle cuán estricta es la detección fuera de tema. Permisivo permite más flexibilidad, mientras que estricto impone conversaciones relacionadas solo con el curso.';
$string['off_topic_strictness_moderate'] = 'Moderado';
$string['off_topic_strictness_permissive'] = 'Permisivo';
$string['off_topic_strictness_strict'] = 'Estricto';
$string['open'] = 'Abrir Tutor IA';
$string['or_click_to_browse'] = 'o haga clic para buscar';
$string['pluginname'] = 'Tutor IA';
$string['position_custom'] = 'Posición personalizada';
$string['position_left'] = 'Esquina inferior izquierda';
$string['position_preset'] = 'Posición predefinida';
$string['position_right'] = 'Esquina inferior derecha';
$string['position_x'] = 'Posición horizontal (X)';
$string['position_x_help'] = 'Distancia desde el borde izquierdo. Ejemplos: 2rem, 20px, 5%. Use valores negativos para posicionar desde el borde derecho.';
$string['position_x_label'] = 'X: {$a->value} (desde {$a->ref})';
$string['position_y'] = 'Posición vertical (Y)';
$string['position_y_help'] = 'Distancia desde el borde inferior. Ejemplos: 6rem, 80px, 10%. Use valores negativos para posicionar desde el borde superior.';
$string['position_y_label'] = 'Y: {$a->value} (desde {$a->ref})';
$string['positiondisplay_corner'] = 'Posición: esquina {$a->preset} | Cajón: {$a->drawer}';
$string['positiondisplay_custom'] = 'Posición: X: {$a->x}, Y: {$a->y} | Cajón: {$a->drawer}';
$string['preview'] = 'Vista previa en vivo';
$string['ref_bottom'] = 'Abajo';
$string['ref_left'] = 'Izquierda';
$string['ref_right'] = 'Derecha';
$string['ref_top'] = 'Arriba';
$string['reference_edge_x'] = 'Borde de referencia horizontal';
$string['reference_edge_y'] = 'Borde de referencia vertical';
$string['selected'] = 'seleccionado';
$string['selection_indicator'] = '{$a} líneas seleccionadas';
$string['selectionformat'] = '{$a->lines} {$a->linetext}, {$a->chars} {$a->chartext} seleccionado';
$string['sendmessage'] = 'Enviar mensaje';
$string['servicebot_firstname'] = 'Tutor';
$string['servicebot_lastname'] = 'AI';
$string['sessionnotready'] = 'La sesión de Tutor IA no está lista. Por favor, inténtelo de nuevo.';
$string['student'] = 'Estudiante';
$string['teacher'] = 'Profesor';
$string['tutor_disabled_notice'] = 'El Tutor IA está actualmente deshabilitado para este curso. Los estudiantes no verán la interfaz de chat.';
$string['tutor_status'] = 'Estado del Tutor IA';
$string['tutorcustomization'] = 'Personalización del Tutor';
$string['tutorname_default'] = 'Tutor IA';
$string['tutorname_setting'] = 'Nombre del tutor';
$string['tutorname_setting_desc'] = 'Configure el nombre que se mostrará en el encabezado del chat. Puede usar {teachername} para mostrar el nombre real del profesor del curso, o introducir un nombre personalizado. Ejemplos: "{teachername}" mostrará "Juan Pérez", "Asistente IA" mostrará "Asistente IA".';
$string['typemessage'] = 'Escriba su mensaje...';
$string['unauthorized'] = 'Acceso no autorizado';
$string['upload_files'] = 'Subir archivos';
$string['upload_material'] = 'Subir PDF';
$string['welcomemessage'] = '¡Hola! Soy tu asistente de IA. ¿Cómo puedo ayudarte hoy?';
$string['welcomemessage_default'] = '¡Hola! Soy {teachername}, tu asistente de IA. ¿Cómo puedo ayudarte hoy?';
$string['welcomemessage_setting'] = 'Mensaje de bienvenida';
$string['welcomemessage_setting_desc'] = 'Personalice el mensaje de bienvenida que se muestra cuando se abre el chat. Puede usar marcadores de posición: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Ayer';
