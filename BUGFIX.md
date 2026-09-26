# BUGFIX.md

**Data da correção:** 26/09/2026
**Corrigido por:** Douglas Oliveira (Dev Júnior)

---

## O que era o bug

<!-- Descreva tecnicamente: arquivo, linha, qual era o problema -->
O problema estava no método store do arquivo EntregaController.php, responsável pela criação de novas entregas.

A validação da transportadora verificava apenas se o identificador informado existia na tabela transportadoras. Dessa forma, uma transportadora que já havia sido desativada também era considerada válida para a criação de uma nova entrega.

A regra do sistema considera uma transportadora ativa quando o campo deleted_at está preenchido com NULL. Quando esse campo possui uma data, a transportadora está inativa.

A validação foi ajustada para considerar se a transportadora está ativa antes de permitir a criação da entrega. Com isso, transportadoras inativas não podem mais ser vinculadas a novas entregas.
---

## Resposta para a Camila (Operações)

<!-- Escreva como se fosse uma mensagem para ela no chat do time.
     Sem código, sem termos técnicos.
     Explique: o que acontecia, o que foi corrigido,
     se as entregas cadastradas indevidamente precisam de alguma ação. -->

     Olá, Camila!

     Como vai?

     Obrigado por nos alertar sobre a falha no cadastro das entregas.

     Após a análise, identificamos que o sistema permitia criar novas entregas vinculadas a transportadoras que já haviam sido desativadas. A validação foi corrigida e, a partir de agora, novas entregas não podem mais ser cadastradas utilizando uma transportadora inativa.

     Também verificamos as entregas que já estavam cadastradas e a correção não realizou nenhuma alteração nelas. Portanto, as entregas existentes não foram afetadas pela correção.

---

## Como reproduzir (antes da correção)

1.Consultar as transportadoras cadastradas e identificar uma transportadora inativa.
2.Verificar que a transportadora 3 estava inativa.
3.Enviar uma requisição POST /entregas utilizando id_transportadora = 3.
4.Antes da correção, o sistema permitia a criação da entrega e retornava status code 201.

## Como verificar que está corrigido

Cenário 1 — transportadora ativa

1.Selecionar uma transportadora ativa.
2.Enviar uma requisição POST /entregas utilizando o ID da transportadora.
3.Verificar que a entrega é criada normalmente.
4.O sistema deve retornar status code 201.

Cenário 2 — transportadora inativa

1.Selecionar a transportadora 3, que possui deleted_at preenchido.
2.Enviar uma requisição POST /entregas utilizando id_transportadora = 3.
3.O sistema deve rejeitar a criação da entrega.
4.O sistema deve retornar status code 403 com a mensagem Transportadora inativa.
5.Verificar no banco de dados que nenhuma nova entrega foi criada para essa tentativa.