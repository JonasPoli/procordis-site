# Spec Delta

## ADDED Requirements

### Requirement: reCAPTCHA nos formulários de contato
Com as chaves do reCAPTCHA v3 configuradas, o sistema SHALL conferir junto ao Google o token enviado pelo formulário de `/contato` (ação `contato`) e pelo formulário da home em `POST /api/contact` (campo `recaptcha_token`, ação `contato_home`) antes de gravar. O contato SHALL ser aceito só com token válido, com a ação esperada e com nota igual ou maior que a nota mínima configurada (0,5 por padrão).

#### Scenario: Visitante real na página Contato
- **WHEN** um visitante preenche o formulário de `/contato` e o Google devolve nota 0,9 para a ação `contato`
- **THEN** a mensagem é gravada, os e-mails saem como antes e o visitante vê a mensagem de sucesso

#### Scenario: Visitante real na home
- **WHEN** a home envia um contato com `recaptcha_token` e o Google devolve nota 0,7 para a ação `contato_home`
- **THEN** a mensagem é gravada e a resposta é `{"success": true}`

#### Scenario: Token de outra ação
- **WHEN** a home envia um token gerado para a ação `contato` e o Google confirma o token com a ação `contato`
- **THEN** o contato é recusado como na regra de contato recusado

### Requirement: Contato recusado pelo reCAPTCHA
Quando o token faltar ou o Google o recusar (token inválido, vencido ou repetido, ação diferente ou nota abaixo da mínima), o sistema MUST NOT gravar a mensagem nem enviar e-mail. Em `/contato` SHALL mostrar o formulário com os dados preenchidos e "Não foi possível confirmar o envio. Tente de novo em alguns segundos."; na API SHALL responder 400 com esse texto em `error`. A recusa SHALL ir para o log com a nota, a ação e o IP, sem nome, e-mail ou mensagem.

#### Scenario: Robô chamando a API sem token
- **WHEN** chega em `POST /api/contact` um JSON com nome, e-mail e mensagem, sem `recaptcha_token`
- **THEN** a resposta é 400 com `{"error": "Não foi possível confirmar o envio. Tente de novo em alguns segundos."}`
- **AND** nada é gravado e nenhum e-mail sai

#### Scenario: Nota baixa na página Contato
- **WHEN** o Google devolve nota 0,1 para o token do formulário de `/contato`
- **THEN** nada é gravado, nenhum e-mail sai e o formulário volta preenchido com a mensagem de recusa
- **AND** o log registra a recusa com a nota 0,1, sem os dados do visitante

### Requirement: Google fora do ar na verificação
Quando a conferência do token não tiver resposta do Google (erro de rede, tempo esgotado ou resposta que não é JSON), o sistema SHALL aceitar o contato como se não houvesse reCAPTCHA e registrar um aviso no log. Token presente e recusado pelo Google não é falha do Google e segue a regra de contato recusado.

#### Scenario: Tempo esgotado
- **WHEN** um visitante envia o formulário de `/contato` e a conferência no Google não responde a tempo
- **THEN** a mensagem é gravada, os e-mails saem e o log recebe um aviso de falha do Google

### Requirement: Contato sem chave do reCAPTCHA
Sem as chaves configuradas, o sistema SHALL aceitar os contatos sem conferir token, MUST NOT carregar o script do Google nas páginas e SHALL registrar um aviso no log a cada contato aceito assim.

#### Scenario: Ambiente local sem chave
- **WHEN** as chaves estão vazias e um visitante envia o formulário de `/contato`
- **THEN** a mensagem é gravada como antes desta mudança e a página não carrega o script do reCAPTCHA

### Requirement: Script do reCAPTCHA sob demanda
O sistema MUST NOT carregar o script do reCAPTCHA na abertura das páginas. O script SHALL carregar só quando o visitante entrar num dos formulários de contato, e o token SHALL ser pedido no envio, para não vencer enquanto o visitante escreve. Se o script não carregar, o envio SHALL seguir sem token e o servidor decide pelas regras acima.

#### Scenario: Abertura da home
- **WHEN** um visitante abre a home e não toca no formulário de contato
- **THEN** a página não faz nenhuma requisição a `www.google.com/recaptcha`

#### Scenario: Visitante demora para escrever
- **WHEN** o visitante entra no formulário de `/contato`, escreve por 5 minutos e envia
- **THEN** o token enviado é gerado no momento do envio e o contato é aceito

### Requirement: Aviso do reCAPTCHA
Com as chaves configuradas, o sistema SHALL mostrar abaixo de cada formulário de contato o aviso "Este site é protegido pelo reCAPTCHA, e se aplicam a Política de Privacidade e os Termos de Serviço do Google.", com os links do Google, e SHALL esconder o selo flutuante do reCAPTCHA.

#### Scenario: Formulário da home
- **WHEN** um visitante abre a home com as chaves configuradas
- **THEN** o aviso com os dois links aparece abaixo do formulário de contato e o selo flutuante não aparece
