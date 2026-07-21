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
 * Russian language strings for Tutor-IA plugin.
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['avatar'] = 'Аватар ИИ-тьютора';
$string['avatar_desc'] = 'Выберите аватар для отображения на плавающей кнопке чата ИИ-тьютора. Если ничего не выбрано или файл не существует, по умолчанию будет использоваться Аватар 1.';
$string['avatar_position'] = 'Позиция аватара';
$string['avatar_position_desc'] = 'Настройте, где будет отображаться плавающая кнопка аватара ИИ-тьютора. Выберите предустановленную позицию в углу или настройте точные координаты X,Y. Предварительный просмотр показывает, как это будет выглядеть.';
$string['cachedef_course_knowledge'] = 'Кэш предварительно загруженных знаний о курсе, используемых чатом';
$string['cachedef_schema_cache'] = 'Кэш схем функций веб-служб Moodle';
$string['char'] = 'символ';
$string['chars'] = 'символов';
$string['clear_selection'] = 'Очистить выделение';
$string['close'] = 'Закрыть ИИ-тьютор';
$string['configuration_error'] = 'Ошибка конфигурации';
$string['connection_interrupted'] = '[Соединение прервано]';
$string['course_materials'] = 'Материалы курса (PDF)';
$string['ctx_loc_activity'] = 'Местоположение: Элемент курса';
$string['ctx_loc_admin'] = 'Местоположение: Администрирование';
$string['ctx_loc_calendar'] = 'Местоположение: Календарь';
$string['ctx_loc_course'] = 'Местоположение: Курс';
$string['ctx_loc_dashboard'] = 'Местоположение: Личный кабинет';
$string['ctx_loc_files'] = 'Местоположение: Файлы';
$string['ctx_loc_gradebook'] = 'Местоположение: Журнал оценок';
$string['ctx_loc_messages'] = 'Местоположение: Сообщения';
$string['ctx_loc_profile'] = 'Местоположение: Профиль';
$string['custom_prompt'] = 'Пользовательский промпт';
$string['custom_prompt_desc'] = 'Пользовательские инструкции для управления поведением ИИ-тьютора. Используйте это поле, чтобы предоставить конкретные рекомендации, тон или границы знаний для тьютора.';
$string['customavatar'] = 'Пользовательский аватар';
$string['customavatar_desc'] = 'Загрузите собственное изображение аватара. Это переопределит выбранный предустановленный аватар.';
$string['customavatar_dimensions'] = 'Рекомендуемые размеры: 200x200 пикселей. Поддерживаемые форматы: PNG, JPG, JPEG, SVG. Максимальный размер файла: 512КБ.';
$string['drawer_side'] = 'Сторона открытия панели';
$string['drawer_side_help'] = 'Выберите, с какой стороны будет открываться панель чата. Это не зависит от положения кнопки аватара.';
$string['drawer_side_left'] = 'Открыть слева';
$string['drawer_side_right'] = 'Открыть справа';
$string['dttutor:use'] = 'Использовать ИИ-тьютор';
$string['edit_cancel'] = 'Отмена';
$string['edit_message'] = 'Редактировать сообщение';
$string['edit_save'] = 'Сохранить';
$string['editing_message'] = 'Редактирование сообщения';
$string['enable_tutor_for_course'] = 'Включить ИИ-тьютор для этого курса';
$string['enable_tutor_for_course_help'] = 'Если включено, ИИ-тьютор будет доступен для студентов и преподавателей в этом курсе. Глобальная настройка плагина также должна быть включена.';
$string['enabled'] = 'Включить чат';
$string['enabled_desc'] = 'Включить или отключить чат ИИ-тьютора глобально';
$string['error_api_not_configured'] = 'Конфигурация API отсутствует. Пожалуйста, проверьте настройки.';
$string['error_attempt_later'] = 'Произошла ошибка. Пожалуйста, попробуйте позже.';
$string['error_empty_message'] = 'Сообщение не может быть пустым';
$string['error_establish_sse_connection'] = '[Ошибка] Не удалось установить соединение SSE';
$string['error_insufficient_tokens'] = 'Недостаточно кредитов ИИ для обработки вашего запроса. Пожалуйста, свяжитесь с администратором для добавления кредитов и продолжения использования ИИ-тьютора.';
$string['error_insufficient_tokens_short'] = 'Недостаточно кредитов';
$string['error_internal'] = 'Внутренняя ошибка: {$a}';
$string['error_invalid_coordinates'] = 'Неверные координаты. Пожалуйста, используйте допустимые значения CSS (например, 10px, 2rem, 50%)';
$string['error_invalid_message'] = 'Пожалуйста, введите корректное сообщение';
$string['error_invalid_position'] = 'Неверные данные позиции';
$string['error_license_fallback'] = 'Ошибка лицензии: {$a}';
$string['error_license_fallback_short'] = 'Ошибка лицензии';
$string['error_license_not_allowed'] = 'Ваша лицензия не позволяет доступ к сервису ИИ-тьютор. Пожалуйста, свяжитесь с администратором для проверки статуса лицензии или обновления плана.';
$string['error_license_not_allowed_short'] = 'Ошибка лицензии';
$string['error_message_too_long'] = '[Ошибка] Сообщение слишком длинное. Максимум 4000 символов.';
$string['error_metadata_too_large'] = 'Метаданные, отправленные с вашим сообщением, слишком велики. Пожалуйста, попробуйте снова.';
$string['error_no_credits'] = 'Недостаточно доступных кредитов ИИ.';
$string['error_no_credits_fallback'] = 'Недостаточно кредитов: {$a}';
$string['error_no_credits_short'] = 'Нет доступных кредитов';
$string['error_selected_text_too_large'] = 'Выбранный текст слишком велик. Пожалуйста, выберите меньший фрагмент.';
$string['error_unexpected'] = 'Произошла непредвиденная ошибка. Пожалуйста, попробуйте снова.';
$string['error_unknown'] = 'Произошла неизвестная ошибка. Пожалуйста, попробуйте снова.';
$string['line'] = 'строка';
$string['lines'] = 'строк';
$string['loading'] = 'Загрузка...';
$string['manage_tutor'] = 'Управление ИИ-тьютором';
$string['material_deleted'] = 'Материал успешно удален';
$string['material_uploaded'] = 'Материал успешно загружен';
$string['off_topic_detection_enabled'] = 'Включить обнаружение оффтопика';
$string['off_topic_detection_enabled_desc'] = 'Если включено, ИИ-тьютор будет обнаруживать сообщения не по теме и реагировать на них в соответствии с уровнем строгости, настроенным ниже.';
$string['off_topic_strictness'] = 'Строгость к оффтопику';
$string['off_topic_strictness_desc'] = 'Управляйте строгостью обнаружения оффтопика. "Либеральный" допускает больше гибкости, а "Строгий" разрешает только разговоры, связанные с курсом.';
$string['off_topic_strictness_moderate'] = 'Умеренный';
$string['off_topic_strictness_permissive'] = 'Либеральный';
$string['off_topic_strictness_strict'] = 'Строгий';
$string['open'] = 'Открыть ИИ-тьютор';
$string['pluginname'] = 'ИИ-тьютор';
$string['position_custom'] = 'Пользовательская позиция';
$string['position_left'] = 'Нижний левый угол';
$string['position_preset'] = 'Предустановленная позиция';
$string['position_right'] = 'Нижний правый угол';
$string['position_x'] = 'Горизонтальная позиция (X)';
$string['position_x_help'] = 'Расстояние от левого края. Примеры: 2rem, 20px, 5%. Используйте отрицательные значения для позиционирования от правого края.';
$string['position_y'] = 'Вертикальная позиция (Y)';
$string['position_y_help'] = 'Расстояние от нижнего края. Примеры: 6rem, 80px, 10%. Используйте отрицательные значения для позиционирования от верхнего края.';
$string['positiondisplay_corner'] = 'Позиция: угол {$a->preset} | Панель: {$a->drawer}';
$string['positiondisplay_custom'] = 'Позиция: X: {$a->x}, Y: {$a->y} | Панель: {$a->drawer}';
$string['preview'] = 'Предварительный просмотр';
$string['ref_bottom'] = 'Низ';
$string['ref_left'] = 'Лево';
$string['ref_right'] = 'Право';
$string['ref_top'] = 'Верх';
$string['reference_edge_x'] = 'Горизонтальный край отсчета';
$string['reference_edge_y'] = 'Вертикальный край отсчета';
$string['selected'] = 'выбрано';
$string['sendmessage'] = 'Отправить сообщение';
$string['servicebot_firstname'] = 'Тьютор';
$string['servicebot_lastname'] = 'ИИ';
$string['sessionnotready'] = 'Сессия ИИ-тьютора не готова. Пожалуйста, попробуйте снова.';
$string['student'] = 'Студент';
$string['teacher'] = 'Преподаватель';
$string['tool_call_webservice_desc'] = 'Вызывает функцию веб-службы Moodle. Функция сработает только если у пользователя есть необходимые права в Moodle.';
$string['tool_call_webservice_function'] = 'Имя функции веб-службы (например, core_user_get_users, core_course_get_courses).';
$string['tool_call_webservice_params'] = 'Параметры, передаваемые функции в виде пар «ключ-значение».';
$string['tool_ws_describe_desc'] = 'Получает полную документацию по определённой функции веб-службы Moodle: описание, параметры с типами и структуру возвращаемых данных.';
$string['tool_ws_describe_wsname'] = 'Точное имя функции веб-службы (например, core_user_create_users, core_course_get_courses).';
$string['tool_ws_search_desc'] = 'Поиск функций веб-служб Moodle по намерению или ключевым словам. Возвращает имя функции, компонент, описание, параметры и возвращаемые значения.';
$string['tool_ws_search_limit'] = 'Максимальное количество результатов (по умолчанию: 20, макс.: 30).';
$string['tool_ws_search_query'] = 'Намерение или ключевые слова на АНГЛИЙСКОМ (например, "create user", "enrol student", "get course grades").';
$string['tutor_disabled_notice'] = 'ИИ-тьютор в настоящее время отключен для этого курса. Студенты не увидят интерфейс чата.';
$string['tutor_status'] = 'Статус ИИ-тьютора';
$string['tutorcustomization'] = 'Настройка тьютора';
$string['tutorname_default'] = 'ИИ-тьютор';
$string['tutorname_setting'] = 'Имя тьютора';
$string['tutorname_setting_desc'] = 'Настройте имя для отображения в заголовке чата. Вы можете использовать {teachername}, чтобы показать настоящее имя преподавателя курса, или ввести собственное имя. Примеры: "{teachername}" покажет "Иван Петров", "ИИ-ассистент" покажет "ИИ-ассистент".';
$string['typemessage'] = 'Введите ваше сообщение...';
$string['welcomemessage'] = 'Привет! Я ваш ИИ-ассистент. Чем я могу помочь вам сегодня?';
$string['welcomemessage_default'] = 'Привет! Я {teachername}, ваш ИИ-ассистент. Чем я могу помочь вам сегодня?';
$string['welcomemessage_setting'] = 'Приветственное сообщение';
$string['welcomemessage_setting_desc'] = 'Настройте приветственное сообщение, отображаемое при открытии чата. Вы можете использовать заполнители: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Вчера';
