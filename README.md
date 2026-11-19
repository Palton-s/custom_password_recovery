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

## Segurança

- **Nenhuma senha é enviada por e-mail.** Só vai um link com token aleatório de 32 caracteres, de uso único e com prazo
  de validade. Cada novo pedido invalida os links anteriores do usuário.
- **Pedir a recuperação não altera nada na conta.** A senha atual continua valendo até o dono do e-mail definir uma nova.
- **Limites de tentativas configuráveis:** por IP (contando também identificadores inexistentes) e por conta.
- **reCAPTCHA opcional**, usando as chaves já configuradas no Moodle.
- **Proteção CSRF** pelo `moodleform` (`sesskey`).
- **O link é montado a partir do `wwwroot` do site**, nunca do cabeçalho `Host` da requisição.
- **O token sai da barra de endereço** logo ao abrir o link: ele vai para a sessão e a página é redirecionada.
- **Se nenhum e-mail puder ser enviado**, o token é descartado e o usuário é avisado.
- **Cada envio fica registrado no log de eventos** (`Link de recuperação de senha enviado`), sem guardar os endereços.
- **Sem dependências externas:** usa o SMTP e as funções de redefinição de senha do próprio Moodle.
- O formulário segue o padrão Post/Redirect/Get, então atualizar a página não reenvia o pedido.

**Recomendação:** bloqueie a edição do campo do e-mail secundário pelo próprio usuário (opção "Bloqueado" no campo de
perfil). O Moodle exige confirmação quando o e-mail principal muda, mas não quando um campo personalizado muda. Se o campo
ficar editável, quem entrar numa conta uma vez pode colocar o próprio e-mail ali e recuperar a senha dessa conta depois.

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

## Observações

- Para mostrar os e-mails de destino, a tela confirma que existe uma conta para aquele CPF. Os limites de tentativas,
  o reCAPTCHA e o mascaramento reduzem o risco de alguém usar isso para descobrir quem tem conta, mas não o eliminam.
- Com `forgottenpasswordurl` preenchida, a página padrão `/login/forgot_password.php` redireciona para o plugin,
  e o aplicativo Moodle também abre o plugin. Se o plugin for desativado com essa configuração ativa, a página dele
  mostra um aviso de "indisponível" (em vez de entrar em loop). Para voltar à recuperação padrão, limpe `forgottenpasswordurl`.
- Links já enviados continuam funcionando mesmo com o plugin desativado.
- Os limites de tentativas ficam no cache de aplicação do Moodle (MUC). Em ambientes com vários servidores web, o cache
  de aplicação precisa ser compartilhado (Redis, por exemplo) para que os limites valham entre os servidores.
- Testes: `vendor/bin/phpunit local/custom_password_recovery/tests/manager_test.php`.
