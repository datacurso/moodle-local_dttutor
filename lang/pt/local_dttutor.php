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

$string['accepted_files_pdf_only'] = 'Apenas arquivos PDF são aceitos (máximo 50 arquivos)';
$string['avatar'] = 'Avatar do Tutor IA';
$string['avatar_desc'] = 'Selecione o avatar para exibir no botão de chat flutuante do Tutor IA. Se nenhum for selecionado ou o arquivo não existir, o Avatar 1 será usado por padrão.';
$string['avatar_position'] = 'Posição do avatar';
$string['avatar_position_desc'] = 'Configure onde o botão flutuante do avatar do Tutor IA será exibido. Escolha uma posição predefinida em um canto ou personalize as coordenadas X,Y exatas. A visualização ao vivo mostra como aparecerá.';
$string['cachedef_sessions'] = 'Cache para sessões de chat do Tutor IA';
$string['char'] = 'caractere';
$string['chars'] = 'caracteres';
$string['choose_files'] = 'Escolher arquivos';
$string['clear_selection'] = 'Limpar seleção';
$string['close'] = 'Fechar Tutor IA';
$string['configuration_error'] = 'Erro de configuração';
$string['configure_now'] = 'Configurar agora';
$string['connection_interrupted'] = '[Conexão interrompida]';
$string['course_custom_prompt'] = 'Prompt personalizado específico do curso';
$string['course_custom_prompt_help'] = 'Este prompt personalizado substitui a configuração global apenas para este curso. Deixe vazio para usar o prompt global.';
$string['course_materials'] = 'Materiais do curso (PDFs)';
$string['course_materials_help'] = 'Envie arquivos PDF adicionais que o tutor IA deve consultar ao responder perguntas.';
$string['custom_prompt'] = 'Prompt personalizado';
$string['custom_prompt_desc'] = 'Instruções personalizadas para controlar o comportamento do tutor IA. Use este campo para fornecer diretrizes específicas, tom ou limites de conhecimento para o tutor.';
$string['customavatar'] = 'Avatar personalizado';
$string['customavatar_desc'] = 'Envie sua própria imagem de avatar personalizada. Isso substituirá o avatar predefinido selecionado.';
$string['customavatar_dimensions'] = 'Dimensões recomendadas: 200x200 pixels. Formatos suportados: PNG, JPG, JPEG, SVG. Tamanho máximo do arquivo: 512KB.';
$string['drag_drop_upload'] = 'Arraste e solte arquivos PDF aqui';
$string['drag_drop_upload_or_browse'] = 'ou clique para procurar';
$string['drawer_side'] = 'Lado de abertura da gaveta';
$string['drawer_side_help'] = 'Escolha de que lado a gaveta de chat abrirá. Isso é independente da posição do botão de avatar.';
$string['drawer_side_left'] = 'Abrir da esquerda';
$string['drawer_side_right'] = 'Abrir da direita';
$string['dttutor:use'] = 'Usar Tutor IA';
$string['enable_tutor_for_course'] = 'Ativar Tutor IA para este curso';
$string['enable_tutor_for_course_help'] = 'Quando ativado, o Tutor IA estará disponível para estudantes e professores neste curso. A configuração global do plugin também deve estar ativada.';
$string['enabled'] = 'Ativar Chat';
$string['enabled_desc'] = 'Ativar ou desativar o chat do Tutor IA globalmente';
$string['error_api_not_configured'] = 'A configuração da API está ausente. Por favor, verifique suas configurações.';
$string['error_api_request_failed'] = 'Erro de solicitação da API: {$a}';
$string['error_attempt_later'] = 'Ocorreu um erro. Por favor, tente novamente mais tarde.';
$string['error_cache_unavailable'] = 'O serviço de chat está temporariamente indisponível. Por favor, tente recarregar a página.';
$string['error_empty_message'] = 'A mensagem não pode estar vazia';
$string['error_establish_sse_connection'] = '[Erro] Não foi possível estabelecer conexão SSE';
$string['error_http_code'] = 'Erro HTTP {$a}';
$string['error_insufficient_tokens'] = 'Não há créditos de IA suficientes disponíveis para processar sua solicitação. Por favor, entre em contato com seu administrador para adicionar mais créditos e continuar usando o Tutor IA.';
$string['error_insufficient_tokens_short'] = 'Créditos insuficientes';
$string['error_internal'] = 'Erro interno: {$a}';
$string['error_invalid_api_response'] = 'Resposta da API inválida';
$string['error_invalid_coordinates'] = 'Coordenadas inválidas. Por favor, use valores CSS válidos (ex. 10px, 2rem, 50%)';
$string['error_invalid_message'] = 'Por favor, digite uma mensagem válida';
$string['error_invalid_position'] = 'Dados de posição inválidos';
$string['error_license_fallback'] = 'Erro de licença: {$a}';
$string['error_license_fallback_short'] = 'Erro de licença';
$string['error_license_not_allowed'] = 'Sua licença não permite acesso ao serviço Tutor IA. Por favor, entre em contato com seu administrador para verificar o status de sua licença ou atualizar seu plano.';
$string['error_license_not_allowed_short'] = 'Erro de licença';
$string['error_message_too_long'] = '[Erro] A mensagem é muito longa. Máximo 4000 caracteres.';
$string['error_metadata_too_large'] = 'Os metadados enviados com sua mensagem são muito grandes. Por favor, tente novamente.';
$string['error_no_credits'] = 'Não há créditos de IA suficientes disponíveis.';
$string['error_no_credits_fallback'] = 'Créditos insuficientes: {$a}';
$string['error_no_credits_short'] = 'Nenhum crédito disponível';
$string['error_selected_text_too_large'] = 'O texto selecionado é muito grande. Por favor, selecione uma porção menor.';
$string['error_unexpected'] = 'Ocorreu um erro inesperado. Por favor, tente novamente.';
$string['error_unknown'] = 'Ocorreu um erro desconhecido. Por favor, tente novamente.';
$string['error_webservice_not_configured'] = 'O chat do Tutor IA não está configurado corretamente e está indisponível no momento.';
$string['error_webservice_not_configured_action'] = 'Por favor, entre em contato com o administrador do site ou relate este problema para ativar o serviço de chat.';
$string['error_webservice_not_configured_admin'] = 'O serviço web do Provedor de IA Datacurso precisa ser configurado antes de usar o Tutor IA. <a href="{$a}" target="_blank">Clique aqui para configurá-lo agora</a>.';
$string['error_webservice_not_configured_admin_inline'] = 'O serviço web do Provedor de IA Datacurso precisa ser configurado antes de usar o Tutor IA.';
$string['error_webservice_not_configured_short'] = 'Serviço de chat indisponível';
$string['last_indexed'] = 'Última sincronização: {$a}';
$string['line'] = 'linha';
$string['lines'] = 'linhas';
$string['loading'] = 'Carregando...';
$string['manage_tutor'] = 'Gerenciamento do Tutor IA';
$string['material_deleted'] = 'Material excluído com sucesso';
$string['material_uploaded'] = 'Material enviado com sucesso';
$string['off_topic_detection_enabled'] = 'Ativar detecção fora de tópico';
$string['off_topic_detection_enabled_desc'] = 'Quando ativado, o tutor IA detectará e responderá a mensagens fora de tópico de acordo com o nível de rigor configurado abaixo.';
$string['off_topic_strictness'] = 'Rigor fora de tópico';
$string['off_topic_strictness_desc'] = 'Controle a rigidez da detecção fora de tópico. Permissivo permite mais flexibilidade, enquanto estrito impõe apenas conversas relacionadas ao curso.';
$string['off_topic_strictness_moderate'] = 'Moderado';
$string['off_topic_strictness_permissive'] = 'Permissivo';
$string['off_topic_strictness_strict'] = 'Estrito';
$string['open'] = 'Abrir Tutor IA';
$string['or_click_to_browse'] = 'ou clique para procurar';
$string['pluginname'] = 'Tutor IA';
$string['position_custom'] = 'Posição personalizada';
$string['position_left'] = 'Canto inferior esquerdo';
$string['position_preset'] = 'Posição predefinida';
$string['position_right'] = 'Canto inferior direito';
$string['position_x'] = 'Posição horizontal (X)';
$string['position_x_help'] = 'Distância da borda esquerda. Exemplos: 2rem, 20px, 5%. Use valores negativos para posicionar a partir da borda direita.';
$string['position_x_label'] = 'X: {$a->value} (de {$a->ref})';
$string['position_y'] = 'Posição vertical (Y)';
$string['position_y_help'] = 'Distância da borda inferior. Exemplos: 6rem, 80px, 10%. Use valores negativos para posicionar a partir da borda superior.';
$string['position_y_label'] = 'Y: {$a->value} (de {$a->ref})';
$string['positiondisplay_corner'] = 'Posição: canto {$a->preset} | Gaveta: {$a->drawer}';
$string['positiondisplay_custom'] = 'Posição: X: {$a->x}, Y: {$a->y} | Gaveta: {$a->drawer}';
$string['preview'] = 'Visualização ao vivo';
$string['ref_bottom'] = 'Inferior';
$string['ref_left'] = 'Esquerda';
$string['ref_right'] = 'Direita';
$string['ref_top'] = 'Superior';
$string['reference_edge_x'] = 'Borda de referência horizontal';
$string['reference_edge_y'] = 'Borda de referência vertical';
$string['selected'] = 'selecionado';
$string['selection_indicator'] = '{$a} linhas selecionadas';
$string['selectionformat'] = '{$a->lines} {$a->linetext}, {$a->chars} {$a->chartext} selecionado';
$string['sendmessage'] = 'Enviar mensagem';
$string['sessionnotready'] = 'A sessão do Tutor IA não está pronta. Por favor, tente novamente.';
$string['student'] = 'Estudante';
$string['teacher'] = 'Professor';
$string['tutor_disabled_notice'] = 'O Tutor IA está desativado para este curso. Os estudantes não verão a interface de chat.';
$string['tutor_status'] = 'Status do Tutor IA';
$string['tutorcustomization'] = 'Personalização do Tutor';
$string['tutorname_default'] = 'Tutor IA';
$string['tutorname_setting'] = 'Nome do tutor';
$string['tutorname_setting_desc'] = 'Configure o nome a ser exibido no cabeçalho do chat. Você pode usar {teachername} para exibir o nome real do professor do curso ou inserir um nome personalizado. Exemplos: "{teachername}" exibirá "João Silva", "Assistente IA" exibirá "Assistente IA".';
$string['typemessage'] = 'Digite sua mensagem...';
$string['unauthorized'] = 'Acesso não autorizado';
$string['upload_files'] = 'Enviar arquivos';
$string['upload_material'] = 'Enviar PDF';
$string['welcomemessage'] = 'Olá! Eu sou seu assistente de IA. Como posso ajudar você hoje?';
$string['welcomemessage_default'] = 'Olá! Eu sou {teachername}, seu assistente de IA. Como posso ajudar você hoje?';
$string['welcomemessage_setting'] = 'Mensagem de boas-vindas';
$string['welcomemessage_setting_desc'] = 'Personalize a mensagem de boas-vindas exibida quando o chat é aberto. Você pode usar marcadores de posição: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Ontem';
