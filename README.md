# Recuperação de senha personalizada (`local_custom_password_recovery`)

Plugin local para Moodle 4.1+ que permite recuperar a senha informando o **CPF** (nome de usuário).
Um **link de redefinição** é enviado para o e-mail da conta **e** para um e-mail secundário guardado em um
campo personalizado de perfil. Depois do envio, a tela mostra para quais e-mails o link foi enviado (mascarados por padrão).

## Como funciona

1. O usuário acessa `/local/custom_password_recovery/index.php` e informa o CPF.
2. O plugin localiza a conta (`username`), confere se a senha dela pode ser redefinida (conta confirmada, não suspensa,
   método de autenticação ativo e que permite redefinição, capacidade `moodle/user:changeownpassword`).
3. Um token é gerado com o mecanismo do próprio Moodle (`core_login_generate_password_reset`, tabela
   `user_password_resets`). Tokens anteriores do usuário são apagados.
4. O link `https://SEU_MOODLE/local/custom_password_recovery/reset.php?token=...` é enviado por `email_to_user()`
   (usa o SMTP configurado no Moodle) para o e-mail principal e para o secundário, sem duplicar endereços.
5. Ao abrir o link, `reset.php` chama a função padrão do Moodle `core_login_process_password_set()`. Ela valida o token,
   que expira conforme `pwresettime` (padrão: 30 min) e só pode ser usado uma vez, pede a nova senha, aplica a política
   de senhas do site e faz o login do usuário. O plugin tem a própria página de redefinição porque, quando
   `forgottenpasswordurl` está preenchida, o Moodle redireciona **todas** as requisições de `/login/forgot_password.php`
   (inclusive as que têm `?token=`) para essa URL.

**A senha atual nunca é alterada pelo plugin.** Ela só muda quando o próprio dono do e-mail define uma nova.

## Problemas do sistema anterior e como foram resolvidos

| Problema no script antigo | Solução no plugin |
|---|---|
| A senha nova ia em texto puro por e-mail | Só é enviado um link com token de uso único e prazo de validade |
| Quem soubesse um CPF trocava a senha da vítima e a deixava sem acesso | O pedido não altera nada; a senha antiga continua valendo |
| Sem limite de tentativas (permitia ataque em massa com lista de CPFs) | Limite por IP (conta também CPFs inexistentes) e por conta, configuráveis |
| Sem proteção contra robôs | reCAPTCHA opcional (usa as chaves já configuradas no Moodle) |
| Sem proteção CSRF | Formulário `moodleform` com `sesskey` |
| Token de webservice com permissão de `core_user_update_users` exposto em arquivo de config | Não usa webservice nem token: roda dentro do Moodle |
| Credenciais SMTP duplicadas em arquivos PHP | Usa o SMTP já configurado no Moodle |
| Página de debug pública expondo dados de usuários | Não existe; os pedidos são registrados no log de eventos (`Link de recuperação de senha enviado`) |
| Senha trocada mesmo quando o e-mail falhava | Se nenhum e-mail sai, o token é descartado e o usuário é avisado |
| Reenvio do formulário ao atualizar a página | Padrão Post/Redirect/Get |

## Instalação

1. Copie esta pasta para `MOODLE/local/custom_password_recovery`. O nome da pasta precisa ser exatamente esse.
2. Acesse **Administração do site → Notificações** para instalar.
3. Configure em **Administração do site → Plugins → Plugins locais → Recuperação de senha personalizada**:
   - **Campo de perfil do e-mail secundário**: escolha o campo (ex.: `email_2aopcao`).
   - **Identificador aceito**: qualquer nome de usuário, ou apenas CPFs válidos.
   - **Mascarar e-mails na tela**, limites de tentativas, reCAPTCHA e texto de contato do suporte.
4. Para que o link **"Esqueceu o seu usuário ou senha?"** da tela de login abra o plugin, vá em
   **Administração do site → Plugins → Autenticação → Gerenciar autenticação** e preencha
   **URL de senha esquecida** (`forgottenpasswordurl`) com `https://SEU_MOODLE/local/custom_password_recovery/index.php`.
5. (Opcional) Ajuste a validade do link em **Administração do site → Segurança → Políticas do site** (`pwresettime`).

Depois de validar o plugin, **remova do servidor os scripts antigos** (`recuperacao_de_senha_a2.php`,
`recuperacao_de_senha_a3.php`, `recuperacao_de_senha_a2_debug.php`) e **revogue o token de webservice** usado por eles.

## Observações

- A tela confirma que existe uma conta para aquele CPF, porque esse comportamento foi pedido. Os limites de
  tentativas, o reCAPTCHA e o mascaramento reduzem o risco, mas não o eliminam.
- Com `forgottenpasswordurl` preenchida, a página padrão `/login/forgot_password.php` redireciona para o plugin,
  e o aplicativo Moodle também abre o plugin. Se o plugin for desativado com essa configuração ativa, a página dele
  mostra um aviso de "indisponível" (em vez de entrar em loop). Para voltar à recuperação padrão, limpe `forgottenpasswordurl`.
- Links já enviados continuam funcionando mesmo com o plugin desativado.
- Os limites de tentativas ficam no cache de aplicação do Moodle (MUC). Em ambientes com vários servidores web, o cache
  de aplicação precisa ser compartilhado (Redis, por exemplo) para que os limites valham entre os servidores.
- Testes: `vendor/bin/phpunit local/custom_password_recovery/tests/manager_test.php`.
