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
 * Portuguese language strings for Tutor-IA plugin.
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['avatar'] = 'Avatar do Tutor IA';
$string['avatar_desc'] = 'Selecione o avatar para exibir no botão de chat flutuante do Tutor IA. Se nenhum for selecionado ou o arquivo não existir, o Avatar 1 será usado por padrão.';
$string['avatar_position'] = 'Posição do avatar';
$string['avatar_position_desc'] = 'Configure onde o botão flutuante do avatar do Tutor IA será exibido. Escolha uma posição predefinida em um canto ou personalize as coordenadas X,Y exatas. A visualização ao vivo mostra como aparecerá.';
$string['cachedef_course_knowledge'] = 'Cache do conhecimento do curso pré-carregado usado pelo chat';
$string['cachedef_sessions'] = 'Cache dos identificadores de sessão do chat do Tutor IA';
$string['char'] = 'caractere';
$string['chars'] = 'caracteres';
$string['clear_selection'] = 'Limpar seleção';
$string['close'] = 'Fechar Tutor IA';
$string['ctx_loc_activity'] = 'Localização: Atividade';
$string['ctx_loc_admin'] = 'Localização: Administração';
$string['ctx_loc_calendar'] = 'Localização: Calendário';
$string['ctx_loc_course'] = 'Localização: Curso';
$string['ctx_loc_dashboard'] = 'Localização: Painel';
$string['ctx_loc_files'] = 'Localização: Arquivos';
$string['ctx_loc_gradebook'] = 'Localização: Notas';
$string['ctx_loc_messages'] = 'Localização: Mensagens';
$string['ctx_loc_profile'] = 'Localização: Perfil';
$string['custom_prompt'] = 'Prompt personalizado';
$string['custom_prompt_desc'] = 'Instruções personalizadas para controlar o comportamento do tutor IA. Use este campo para fornecer diretrizes específicas, tom ou limites de conhecimento para o tutor.';
$string['customavatar'] = 'Avatar personalizado';
$string['customavatar_desc'] = 'Envie sua própria imagem de avatar personalizada. Isso substituirá o avatar predefinido selecionado.';
$string['customavatar_dimensions'] = 'Dimensões recomendadas: 200x200 pixels. Formatos suportados: PNG, JPG, JPEG, SVG. Tamanho máximo do arquivo: 512KB.';
$string['drawer_side'] = 'Lado de abertura da gaveta';
$string['drawer_side_help'] = 'Escolha de que lado a gaveta de chat abrirá. Isso é independente da posição do botão de avatar.';
$string['drawer_side_left'] = 'Abrir da esquerda';
$string['drawer_side_right'] = 'Abrir da direita';
$string['dttutor:use'] = 'Usar Tutor IA';
$string['edit_cancel'] = 'Cancelar';
$string['edit_message'] = 'Editar mensagem';
$string['edit_save'] = 'Salvar';
$string['enable_tutor_for_course'] = 'Ativar Tutor IA para este curso';
$string['enable_tutor_for_course_help'] = 'Quando ativado, o Tutor IA estará disponível para estudantes e professores neste curso. A configuração global do plugin também deve estar ativada.';
$string['enabled'] = 'Ativar Chat';
$string['enabled_desc'] = 'Ativar ou desativar o chat do Tutor IA globalmente';
$string['error_api_not_configured'] = 'A configuração da API está ausente. Por favor, verifique suas configurações.';
$string['error_attempt_later'] = 'Ocorreu um erro. Por favor, tente novamente mais tarde.';
$string['error_history_unavailable'] = 'Não foi possível carregar a conversa anterior. Você pode continuar conversando.';
$string['error_insufficient_tokens'] = 'Não há créditos de IA suficientes disponíveis para processar sua solicitação. Por favor, entre em contato com seu administrador para adicionar mais créditos e continuar usando o Tutor IA.';
$string['error_insufficient_tokens_short'] = 'Créditos insuficientes';
$string['error_internal'] = 'Erro interno: {$a}';
$string['error_invalid_coordinates'] = 'Coordenadas inválidas. Por favor, use valores CSS válidos (ex. 10px, 2rem, 50%)';
$string['error_invalid_message'] = 'Por favor, digite uma mensagem válida';
$string['error_invalid_position'] = 'Dados de posição inválidos';
$string['error_license_fallback'] = 'Erro de licença: {$a}';
$string['error_license_fallback_short'] = 'Erro de licença';
$string['error_license_not_allowed'] = 'Sua licença não permite acesso ao serviço Tutor IA. Por favor, entre em contato com seu administrador para verificar o status de sua licença ou atualizar seu plano.';
$string['error_license_not_allowed_short'] = 'Erro de licença';
$string['error_message_too_long'] = '[Erro] A mensagem é muito longa. Máximo 4000 caracteres.';
$string['error_no_credits_fallback'] = 'Créditos insuficientes: {$a}';
$string['error_ratelimit_exceeded'] = 'O limite de consumo permitido foi excedido. Por favor, tente novamente em {$a}.';
$string['error_tutor_not_available'] = 'O Tutor IA não está disponível para este curso.';
$string['error_unexpected'] = 'Ocorreu um erro inesperado. Por favor, tente novamente.';
$string['error_unknown'] = 'Ocorreu um erro desconhecido. Por favor, tente novamente.';
$string['include_grades'] = 'Enviar as notas do estudante ao tutor IA';
$string['include_grades_desc'] = 'Quando ativado, as notas do próprio estudante no curso são adicionadas ao contexto enviado ao serviço Datacurso AI para que o tutor possa responder a perguntas sobre elas. Desativado por padrão para minimizar os dados pessoais transferidos.';
$string['line'] = 'linha';
$string['lines'] = 'linhas';
$string['loading'] = 'Carregando...';
$string['manage_tutor'] = 'Gerenciamento do Tutor IA';
$string['open'] = 'Abrir Tutor IA';
$string['pluginname'] = 'Tutor IA';
$string['position_custom'] = 'Posição personalizada';
$string['position_left'] = 'Canto inferior esquerdo';
$string['position_preset'] = 'Posição predefinida';
$string['position_right'] = 'Canto inferior direito';
$string['position_x'] = 'Posição horizontal (X)';
$string['position_x_help'] = 'Distância da borda esquerda. Exemplos: 2rem, 20px, 5%. Use valores negativos para posicionar a partir da borda direita.';
$string['position_y'] = 'Posição vertical (Y)';
$string['position_y_help'] = 'Distância da borda inferior. Exemplos: 6rem, 80px, 10%. Use valores negativos para posicionar a partir da borda superior.';
$string['positiondisplay_corner'] = 'Posição: canto {$a->preset} | Gaveta: {$a->drawer}';
$string['positiondisplay_custom'] = 'Posição: X: {$a->x}, Y: {$a->y} | Gaveta: {$a->drawer}';
$string['preview'] = 'Visualização ao vivo';
$string['privacy:export:course_config'] = 'Configuração do tutor no curso';
$string['privacy:export:sessions'] = 'Sessões de chat';
$string['privacy:metadata:datacurso_ai'] = 'As mensagens do chat e o contexto do curso são enviados ao serviço Datacurso AI para gerar as respostas do tutor.';
$string['privacy:metadata:datacurso_ai:cmid'] = 'O ID do módulo do curso que o usuário estava visualizando ao escrever a mensagem.';
$string['privacy:metadata:datacurso_ai:course_structure'] = 'A estrutura do curso visível para o usuário (atividades, seções, datas e notas máximas).';
$string['privacy:metadata:datacurso_ai:custom_prompt'] = 'As instruções personalizadas institucionais configuradas pelo administrador.';
$string['privacy:metadata:datacurso_ai:grades'] = 'As notas do próprio usuário no curso, apenas quando a configuração "Enviar as notas do estudante" está ativada.';
$string['privacy:metadata:datacurso_ai:lang'] = 'O idioma atual do usuário.';
$string['privacy:metadata:datacurso_ai:messages'] = 'As mensagens do chat escritas pelo usuário e as respostas anteriores do tutor.';
$string['privacy:metadata:datacurso_ai:page_url'] = 'A URL da página do Moodle a partir da qual a mensagem foi enviada.';
$string['privacy:metadata:datacurso_ai:selected_text'] = 'O texto que o usuário selecionou na página para perguntar sobre ele.';
$string['privacy:metadata:datacurso_ai:site_id'] = 'O identificador anônimo deste site Moodle.';
$string['privacy:metadata:datacurso_ai:site_url'] = 'A URL deste site Moodle.';
$string['privacy:metadata:datacurso_ai:timezone'] = 'O fuso horário do usuário.';
$string['privacy:metadata:datacurso_ai:userid'] = 'O ID do usuário que envia a mensagem.';
$string['privacy:metadata:local_dttutor_course_config'] = 'Configuração do tutor IA por curso.';
$string['privacy:metadata:local_dttutor_course_config:timemodified'] = 'Quando a configuração foi modificada pela última vez.';
$string['privacy:metadata:local_dttutor_course_config:usermodified'] = 'O ID do usuário que modificou a configuração pela última vez.';
$string['privacy:metadata:local_dttutor_session'] = 'Identificadores das sessões de chat que um usuário abriu com o tutor Datacurso AI, armazenados para que possam ser excluídas remotamente.';
$string['privacy:metadata:local_dttutor_session:cmid'] = 'O ID do módulo do curso ao qual a sessão pertence (0 para todo o curso).';
$string['privacy:metadata:local_dttutor_session:courseid'] = 'O ID do curso ao qual a sessão pertence.';
$string['privacy:metadata:local_dttutor_session:remotesessionid'] = 'O identificador da sessão no serviço Datacurso AI.';
$string['privacy:metadata:local_dttutor_session:timecreated'] = 'Quando a sessão foi criada.';
$string['privacy:metadata:local_dttutor_session:timemodified'] = 'Quando a sessão foi renovada pela última vez.';
$string['privacy:metadata:local_dttutor_session:userid'] = 'O ID do usuário proprietário da sessão.';
$string['ref_bottom'] = 'Inferior';
$string['ref_left'] = 'Esquerda';
$string['ref_right'] = 'Direita';
$string['ref_top'] = 'Superior';
$string['reference_edge_x'] = 'Borda de referência horizontal';
$string['reference_edge_y'] = 'Borda de referência vertical';
$string['selected'] = 'selecionado';
$string['sendmessage'] = 'Enviar mensagem';
$string['student'] = 'Estudante';
$string['teacher'] = 'Professor';
$string['tutor_disabled_notice'] = 'O Tutor IA está desativado para este curso. Os estudantes não verão a interface de chat.';
$string['tutor_status'] = 'Status do Tutor IA';
$string['tutorcustomization'] = 'Personalização do Tutor';
$string['tutorname_default'] = 'Tutor IA';
$string['tutorname_setting'] = 'Nome do tutor';
$string['tutorname_setting_desc'] = 'Configure o nome a ser exibido no cabeçalho do chat. Você pode usar {teachername} para exibir o nome real do professor do curso ou inserir um nome personalizado. Exemplos: "{teachername}" exibirá "João Silva", "Assistente IA" exibirá "Assistente IA".';
$string['typemessage'] = 'Digite sua mensagem...';
$string['welcomemessage_default'] = 'Olá! Eu sou {teachername}, seu assistente de IA. Como posso ajudar você hoje?';
$string['welcomemessage_setting'] = 'Mensagem de boas-vindas';
$string['welcomemessage_setting_desc'] = 'Personalize a mensagem de boas-vindas exibida quando o chat é aberto. Você pode usar marcadores de posição: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Ontem';
