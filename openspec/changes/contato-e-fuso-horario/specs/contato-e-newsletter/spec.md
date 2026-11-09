# Spec Delta

## MODIFIED Requirements

### Requirement: E-mail do formulário da página Contato
Depois de gravar, o sistema SHALL enviar um e-mail separado, de `no-reply@procordis.org.br`, a cada endereço válido da variável de sistema `contact_email_recipients` (a mesma lista do formulário da home, separada por vírgula ou linha), com resposta para o e-mail do visitante e assunto "Novo contato pelo site: <assunto>". Falha no envio MUST NOT desfazer a gravação nem mudar a mensagem de sucesso; o erro vai só para o log.

#### Scenario: E-mail fora do ar
- **WHEN** a gravação dá certo e o envio do e-mail falha
- **THEN** a mensagem continua gravada e o visitante vê a mensagem de sucesso

#### Scenario: Dois destinatários cadastrados
- **WHEN** a variável `contact_email_recipients` tem dois endereços e um visitante envia o formulário de `/contato`
- **THEN** cada endereço recebe o seu próprio e-mail com o nome, o e-mail, o assunto e a mensagem do visitante
- **AND** cada e-mail tem resposta para o e-mail do visitante

#### Scenario: Endereço inválido na lista
- **WHEN** a variável tem um endereço válido e uma linha que não é e-mail
- **THEN** só o endereço válido recebe o e-mail

#### Scenario: Endereço antigo fora da lista
- **WHEN** um visitante envia o formulário de `/contato` e `contato@procordis.com.br` não está na variável
- **THEN** nenhum e-mail é enviado para `contato@procordis.com.br`

## ADDED Requirements

### Requirement: Contato sem destinatário cadastrado
Quando a variável `contact_email_recipients` não existir ou não tiver endereço válido, o sistema SHALL gravar a mensagem do `/contato`, mostrar a mensagem de sucesso, não enviar e-mail e registrar um aviso no log. O sistema MUST NOT usar um endereço fixo no código como reserva.

#### Scenario: Variável vazia
- **WHEN** a variável `contact_email_recipients` está vazia e um visitante envia o formulário de `/contato`
- **THEN** a mensagem é gravada e o visitante vê "Sua mensagem foi enviada com sucesso! Em breve entraremos em contato."
- **AND** nenhum e-mail é enviado e o log recebe um aviso

### Requirement: Falha em um destinatário do Contato
Quando o envio do e-mail do `/contato` falhar para um destinatário, o sistema SHALL continuar o envio para os demais e registrar a falha no log.

#### Scenario: Primeiro destinatário recusado
- **WHEN** a variável tem dois endereços e o envio para o primeiro falha
- **THEN** o segundo endereço recebe o e-mail e a falha do primeiro fica no log
