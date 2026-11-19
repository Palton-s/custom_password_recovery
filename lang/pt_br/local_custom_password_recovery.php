<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings em português do Brasil.
 *
 * @package    local_custom_password_recovery
 * @copyright  2026 CEAD/UnB
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Recuperação de senha personalizada';
$string['privacy:metadata'] = 'O plugin não armazena dados pessoais. Os tokens de redefinição ficam na tabela de redefinição de senha do Moodle e os contadores de limite de tentativas são entradas temporárias de cache.';
$string['cachedef_ratelimit'] = 'Contadores de solicitações de recuperação de senha (limite de tentativas)';
$string['eventrecoverylinksent'] = 'Link de recuperação de senha enviado';

// Página.
$string['pagetitle'] = 'Recuperação de senha';
$string['formintro'] = 'Informe seu CPF (nome de usuário). Enviaremos um link para definir uma nova senha aos e-mails cadastrados na sua conta.';
$string['identifier'] = 'CPF';
$string['sendlink'] = 'Enviar link de recuperação';
$string['backtologin'] = 'Voltar para a página de login';
$string['result_sent'] = 'Um link de redefinição de senha foi enviado para:';
$string['result_sent_details'] = 'O link é válido por {$a} minutos e só pode ser usado uma vez. Sua senha atual continua funcionando até que você defina uma nova. Se não encontrar a mensagem, verifique a caixa de spam.';
$string['result_notfound'] = 'Não encontramos nenhuma conta com os dados informados. Verifique se digitou corretamente.';
$string['result_cannotreset'] = 'A senha desta conta não pode ser redefinida por este formulário.';
$string['result_noemail'] = 'Esta conta não possui nenhum e-mail válido cadastrado, por isso não foi possível enviar o link.';
$string['result_ratelimited'] = 'Muitas solicitações em pouco tempo. Aguarde alguns minutos e tente novamente.';
$string['result_sendfailed'] = 'Não foi possível enviar o e-mail neste momento. Tente novamente mais tarde.';
$string['disabledmessage'] = 'A recuperação de senha está temporariamente indisponível. Entre em contato com o suporte.';

// E-mail.
$string['emailsubject'] = '{$a->sitename}: solicitação de redefinição de senha';
$string['emailbodytext'] = 'Olá, {$a->fullname},

Recebemos uma solicitação de redefinição de senha para a sua conta \'{$a->username}\' em {$a->sitename}.

Para definir uma nova senha, acesse o link abaixo (válido por {$a->minutes} minutos, uso único):

{$a->link}

Se você não fez esta solicitação, apenas ignore esta mensagem: sua senha atual continua válida e nada foi alterado.

{$a->signoff}';
$string['emailbodyhtml'] = '<p>Olá, {$a->fullname},</p>
<p>Recebemos uma solicitação de redefinição de senha para a sua conta <strong>{$a->username}</strong> em {$a->sitename}.</p>
<p>Para definir uma nova senha, clique no link abaixo (válido por {$a->minutes} minutos, uso único):</p>
<p><a href="{$a->link}">{$a->link}</a></p>
<p>Se você não fez esta solicitação, apenas ignore esta mensagem: sua senha atual continua válida e nada foi alterado.</p>
<p>{$a->signoff}</p>';

// Configurações.
$string['setupinfo'] = '<div class="alert alert-info"><p><strong>Configuração:</strong> para que o link "Esqueceu o seu usuário ou senha?" da página de login abra este plugin, acesse <a href="{$a->authsettings}">Administração do site &gt; Plugins &gt; Autenticação &gt; Gerenciar autenticação</a> e preencha <em>URL de senha esquecida</em> (forgottenpasswordurl) com:</p><p><code>{$a->pageurl}</code></p><p>Com essa configuração, a página padrão /login/forgot_password.php (e o aplicativo Moodle) também passam a enviar os usuários para cá, ou seja, este plugin substitui por completo a recuperação padrão. O link enviado por e-mail abre a página de redefinição do próprio plugin.</p><p>A validade do link vem da configuração do Moodle <em>pwresettime</em> (atualmente {$a->minutes} minutos).</p></div>';
$string['enabled'] = 'Ativado';
$string['enabled_desc'] = 'Quando desativado, a página redireciona para a recuperação de senha padrão do Moodle.';
$string['secondaryemailfield'] = 'Campo de perfil do e-mail secundário';
$string['secondaryemailfield_desc'] = 'Campo personalizado de perfil que contém o e-mail secundário (pessoal). O link é enviado para o e-mail da conta e também para este endereço.';
$string['identifiermode'] = 'Identificador aceito';
$string['identifiermode_desc'] = 'Como o valor digitado é comparado com o nome de usuário. Nos dois modos a formatação do CPF (pontos, traços, espaços) é removida.';
$string['identifiermode_username'] = 'Qualquer nome de usuário (CPF ou não)';
$string['identifiermode_cpf'] = 'Somente CPFs válidos (os dígitos verificadores são conferidos)';
$string['maskemails'] = 'Mascarar e-mails na tela';
$string['maskemails_desc'] = 'Mostra os endereços parcialmente ocultos (ex.: jo*****@unb.br). Recomendado: qualquer pessoa que saiba um CPF pode usar o formulário, então exibir os endereços completos exporia dados pessoais.';
$string['securityheading'] = 'Proteção contra abuso';
$string['maxperip'] = 'Solicitações por IP';
$string['maxperip_desc'] = 'Máximo de solicitações (com ou sem sucesso) do mesmo endereço IP dentro da janela de tempo. 0 desativa o limite.';
$string['maxperaccount'] = 'E-mails por conta';
$string['maxperaccount_desc'] = 'Máximo de e-mails de recuperação enviados para a mesma conta dentro da janela de tempo. 0 desativa o limite.';
$string['ratewindow'] = 'Janela de tempo';
$string['ratewindow_desc'] = 'Janela usada pelos limites acima (máximo de 1 dia).';
$string['userecaptcha'] = 'Usar reCAPTCHA';
$string['userecaptcha_desc'] = 'Adiciona um reCAPTCHA ao formulário. Exige as chaves do reCAPTCHA (recaptchapublickey / recaptchaprivatekey) em Administração do site &gt; Plugins &gt; Autenticação &gt; Gerenciar autenticação.';
$string['contactinfo'] = 'Contato do suporte';
$string['contactinfo_desc'] = 'Texto exibido abaixo das mensagens de erro, ex.: "Se o problema persistir, entre em contato com a equipe de TI pelo e-mail ti.cead@unb.br".';
