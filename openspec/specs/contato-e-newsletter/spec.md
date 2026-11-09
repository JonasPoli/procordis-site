# contato-e-newsletter Specification

## Purpose
Recebe as mensagens de contato e as inscrições na newsletter do site. Há dois caminhos de contato, com destinos de e-mail diferentes: o formulário da página `/contato` (`ContactController`, `Form/ContactType`) e o formulário da home, que envia JSON para `/api/contact` (`Api/ContactApiController`). A newsletter fica no rodapé e envia para `/api/newsletter` (`Api/NewsletterApiController`). O painel lista as mensagens em `/admin/messages` e os inscritos em `/admin/newsletter`. Não há anti-spam nem limite por IP.

## Requirements
### Requirement: Formulário da página Contato
O sistema SHALL exigir em `/contato` nome, e-mail válido, assunto e mensagem, com a mensagem de cada campo em falta (ex.: "Por favor, digite um e-mail válido."). A mensagem aceita SHALL ser gravada com status `new` antes de qualquer e-mail. Se a gravação falhar, SHALL mostrar "Ocorreu um erro ao salvar sua mensagem. Tente novamente." e não enviar e-mail.

#### Scenario: Contato aceito
- **WHEN** o visitante envia o formulário de `/contato` preenchido
- **THEN** a mensagem é gravada
- **AND** a página volta para `/contato` com "Sua mensagem foi enviada com sucesso! Em breve entraremos em contato."

### Requirement: E-mail do formulário da página Contato
Depois de gravar, o sistema SHALL enviar um e-mail de `no-reply@procordis.org.br` para o endereço fixo no código `contato@procordis.com.br`, com resposta para o e-mail do visitante e assunto "Novo contato pelo site: <assunto>". Falha no envio MUST NOT desfazer a gravação nem mudar a mensagem de sucesso; o erro vai só para o log.

#### Scenario: E-mail fora do ar
- **WHEN** a gravação dá certo e o envio do e-mail falha
- **THEN** a mensagem continua gravada e o visitante vê a mensagem de sucesso

### Requirement: Formulário de contato da home
O sistema SHALL aceitar em `POST /api/contact` um JSON com `name`, `email` e `message` (e `subject` e `phone` opcionais; sem assunto grava "Contato pelo Site"). Faltando um dos três obrigatórios, SHALL responder 400 com "Dados inválidos.". O e-mail do visitante não é validado no servidor. Aceita, a mensagem SHALL ser gravada com status `new` e a resposta SHALL ser `{"success": true}`.

#### Scenario: JSON sem mensagem
- **WHEN** a home envia nome e e-mail sem `message`
- **THEN** a resposta é 400 com `{"error": "Dados inválidos."}` e nada é gravado

### Requirement: E-mail do formulário da home
Depois de gravar, o sistema SHALL enviar um e-mail separado, de `noreply@wab.com.br`, a cada endereço válido da variável de sistema `contact_email_recipients` (separados por vírgula ou linha), com o assunto "[Procordis] Novo Contato: <assunto>" e o modelo `emails/contact.html.twig`. Sem a variável, nenhum e-mail sai. Falha no envio MUST NOT mudar a resposta de sucesso.

#### Scenario: Dois destinatários cadastrados
- **WHEN** a variável `contact_email_recipients` tem dois endereços e chega um contato pela home
- **THEN** cada endereço recebe o seu próprio e-mail com os dados do contato

### Requirement: Erro de gravação na API de contato
Se a gravação do contato da home falhar, o sistema SHALL responder 500 com "Erro ao processar mensagem: " seguido do texto da exceção, e registrar o erro no log como crítico.

#### Scenario: Banco fora do ar
- **WHEN** a home envia um contato e o banco não aceita a gravação
- **THEN** a resposta é 500 e a home mostra a mensagem de erro recebida

### Requirement: Inscrição na newsletter
O sistema SHALL aceitar em `POST /api/newsletter` um JSON com `email` e gravá-lo com a data da inscrição. E-mail ausente SHALL responder 400 "E-mail obrigatório."; inválido, 400 "E-mail inválido."; já cadastrado, 400 "Duplicate: Este e-mail já está cadastrado.", que o rodapé mostra como "Já Inscrito". Não há e-mail de confirmação.

#### Scenario: E-mail repetido
- **WHEN** um visitante se inscreve com um e-mail que já está na lista
- **THEN** nada é gravado e o rodapé mostra "Este e-mail já faz parte da nossa lista."

### Requirement: Consulta no painel
O sistema SHALL listar para o Administrador as mensagens de contato e os inscritos da newsletter, dos mais recentes para os mais antigos, e SHALL exportar os inscritos em `newsletter_subscribers.csv` (ID, Email, Data de Inscrição). A listagem de mensagens não altera o status: toda mensagem fica `new`.

#### Scenario: Exportar inscritos
- **WHEN** o administrador clica em exportar na newsletter
- **THEN** o navegador baixa `newsletter_subscribers.csv` com todos os inscritos
