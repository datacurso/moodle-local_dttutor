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
$string['cachedef_sessions'] = 'Кэш идентификаторов сессий чата ИИ-тьютора';
$string['char'] = 'символ';
$string['chars'] = 'символов';
$string['clear_selection'] = 'Очистить выделение';
$string['close'] = 'Закрыть ИИ-тьютор';
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
$string['enable_tutor_for_course'] = 'Включить ИИ-тьютор для этого курса';
$string['enable_tutor_for_course_help'] = 'Если включено, ИИ-тьютор будет доступен для студентов и преподавателей в этом курсе. Глобальная настройка плагина также должна быть включена.';
$string['enabled'] = 'Включить чат';
$string['enabled_desc'] = 'Включить или отключить чат ИИ-тьютора глобально';
$string['error_api_not_configured'] = 'Конфигурация API отсутствует. Пожалуйста, проверьте настройки.';
$string['error_attempt_later'] = 'Произошла ошибка. Пожалуйста, попробуйте позже.';
$string['error_history_unavailable'] = 'Не удалось загрузить предыдущий разговор. Вы можете продолжить общение.';
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
$string['error_no_credits_fallback'] = 'Недостаточно кредитов: {$a}';
$string['error_tutor_not_available'] = 'ИИ-тьютор недоступен для этого курса.';
$string['error_unexpected'] = 'Произошла непредвиденная ошибка. Пожалуйста, попробуйте снова.';
$string['error_unknown'] = 'Произошла неизвестная ошибка. Пожалуйста, попробуйте снова.';
$string['include_grades'] = 'Отправлять оценки студента ИИ-тьютору';
$string['include_grades_desc'] = 'Если включено, собственные оценки текущего студента по курсу добавляются в контекст, отправляемый в сервис Datacurso AI, чтобы тьютор мог отвечать на вопросы о них. По умолчанию отключено для минимизации передаваемых персональных данных.';
$string['line'] = 'строка';
$string['lines'] = 'строк';
$string['loading'] = 'Загрузка...';
$string['manage_tutor'] = 'Управление ИИ-тьютором';
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
$string['privacy:export:course_config'] = 'Настройки тьютора в курсе';
$string['privacy:export:sessions'] = 'Сессии чата';
$string['privacy:metadata:datacurso_ai'] = 'Сообщения чата и контекст курса отправляются в сервис Datacurso AI для формирования ответов тьютора.';
$string['privacy:metadata:datacurso_ai:cmid'] = 'ID модуля курса, который пользователь просматривал при написании сообщения.';
$string['privacy:metadata:datacurso_ai:course_structure'] = 'Структура курса, видимая пользователю (элементы, разделы, даты и максимальные оценки).';
$string['privacy:metadata:datacurso_ai:custom_prompt'] = 'Пользовательские инструкции организации, настроенные администратором.';
$string['privacy:metadata:datacurso_ai:grades'] = 'Собственные оценки пользователя по курсу, только если включена настройка «Отправлять оценки студента».';
$string['privacy:metadata:datacurso_ai:lang'] = 'Текущий язык пользователя.';
$string['privacy:metadata:datacurso_ai:messages'] = 'Сообщения чата, написанные пользователем, и предыдущие ответы тьютора.';
$string['privacy:metadata:datacurso_ai:page_url'] = 'URL страницы Moodle, с которой было отправлено сообщение.';
$string['privacy:metadata:datacurso_ai:selected_text'] = 'Текст, выделенный пользователем на странице для вопроса.';
$string['privacy:metadata:datacurso_ai:site_id'] = 'Анонимный идентификатор этого сайта Moodle.';
$string['privacy:metadata:datacurso_ai:site_url'] = 'URL этого сайта Moodle.';
$string['privacy:metadata:datacurso_ai:timezone'] = 'Часовой пояс пользователя.';
$string['privacy:metadata:datacurso_ai:userid'] = 'ID пользователя, отправляющего сообщение.';
$string['privacy:metadata:local_dttutor_course_config'] = 'Настройки ИИ-тьютора для каждого курса.';
$string['privacy:metadata:local_dttutor_course_config:timemodified'] = 'Время последнего изменения настроек.';
$string['privacy:metadata:local_dttutor_course_config:usermodified'] = 'ID пользователя, последним изменившего настройки.';
$string['privacy:metadata:local_dttutor_session'] = 'Идентификаторы сессий чата, открытых пользователем с тьютором Datacurso AI, сохраняемые для их удаленного удаления.';
$string['privacy:metadata:local_dttutor_session:cmid'] = 'ID модуля курса, к которому относится сессия (0 для всего курса).';
$string['privacy:metadata:local_dttutor_session:courseid'] = 'ID курса, к которому относится сессия.';
$string['privacy:metadata:local_dttutor_session:remotesessionid'] = 'Идентификатор сессии в сервисе Datacurso AI.';
$string['privacy:metadata:local_dttutor_session:timecreated'] = 'Время создания сессии.';
$string['privacy:metadata:local_dttutor_session:timemodified'] = 'Время последнего обновления сессии.';
$string['privacy:metadata:local_dttutor_session:userid'] = 'ID пользователя, которому принадлежит сессия.';
$string['ref_bottom'] = 'Низ';
$string['ref_left'] = 'Лево';
$string['ref_right'] = 'Право';
$string['ref_top'] = 'Верх';
$string['reference_edge_x'] = 'Горизонтальный край отсчета';
$string['reference_edge_y'] = 'Вертикальный край отсчета';
$string['selected'] = 'выбрано';
$string['sendmessage'] = 'Отправить сообщение';
$string['student'] = 'Студент';
$string['teacher'] = 'Преподаватель';
$string['tutor_disabled_notice'] = 'ИИ-тьютор в настоящее время отключен для этого курса. Студенты не увидят интерфейс чата.';
$string['tutor_status'] = 'Статус ИИ-тьютора';
$string['tutorcustomization'] = 'Настройка тьютора';
$string['tutorname_default'] = 'ИИ-тьютор';
$string['tutorname_setting'] = 'Имя тьютора';
$string['tutorname_setting_desc'] = 'Настройте имя для отображения в заголовке чата. Вы можете использовать {teachername}, чтобы показать настоящее имя преподавателя курса, или ввести собственное имя. Примеры: "{teachername}" покажет "Иван Петров", "ИИ-ассистент" покажет "ИИ-ассистент".';
$string['typemessage'] = 'Введите ваше сообщение...';
$string['welcomemessage_default'] = 'Привет! Я {teachername}, ваш ИИ-ассистент. Чем я могу помочь вам сегодня?';
$string['welcomemessage_setting'] = 'Приветственное сообщение';
$string['welcomemessage_setting_desc'] = 'Настройте приветственное сообщение, отображаемое при открытии чата. Вы можете использовать заполнители: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Вчера';
