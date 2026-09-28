# Teste Técnico — Desenvolvedor PHP Júnior

## Contexto

Você acabou de entrar no time de desenvolvimento de um TMS (Transportation Management System). No seu primeiro dia, chegou um bug reportado pelo time de operações e uma nova funcionalidade para implementar.

Seu trabalho: **corrigir o bug e entregar a feature**.

---

## Prazo

**5 dias corridos** a partir do recebimento deste desafio.

---

## Stack

PHP 8.1+ · PDO · MySQL 8+ · [Phinx](https://phinx.org) (migrations e seeds)

---

## Como rodar

```bash
# 1. Configure o ambiente
cp .env.example .env
# edite .env com suas credenciais MySQL

# 2. Instale as dependências
composer install

# 3. Crie as tabelas
vendor/bin/phinx migrate

# 4. Popule os dados iniciais
vendor/bin/phinx seed:run

# 5. Suba o servidor
php -S localhost:8000 public/index.php
```

---

## Sistema atual

Endpoints disponíveis:

```
GET   /transportadoras
POST  /transportadoras
GET   /transportadoras/{id}
PATCH /transportadoras/{id}/desativar
PATCH /transportadoras/{id}/reativar

GET   /entregas
POST  /entregas
GET   /entregas/{id}
PATCH /entregas/{id}/status
```

Dados de seed disponíveis (use os IDs para testar):
- 3 transportadoras (2 ativas, 1 inativa)
- 2 remetentes
- 3 destinatários
- 3 entregas em status variados com histórico de ocorrências

**Fluxo de status:**
```
CRIADA → COLETADA → EM_TRANSITO → SAIU_ENTREGA → ENTREGUE
                                               ↘ DEVOLVIDA
```
Transições inválidas devem retornar `422`.

---

## Suas tarefas

### Tarefa 1 — Corrigir o bug

Leia o arquivo [`BUG_REPORT.md`](./BUG_REPORT.md), reproduza o problema, corrija e preencha o [`BUGFIX.md`](./BUGFIX.md).

### Tarefa 2 — Não conformidades

O time de operações precisa registrar ocorrências de entregas com problema (avaria, recusa, endereço errado, etc.).

**Crie as migrations:**

```
motivos_nao_conformidade
  id        INT UNSIGNED PK AUTO_INCREMENT
  codigo    VARCHAR(30) UNIQUE NOT NULL
  descricao VARCHAR(150) NOT NULL
  ativo     TINYINT(1) NOT NULL DEFAULT 1

nao_conformidades
  id         INT UNSIGNED PK AUTO_INCREMENT
  id_entrega INT UNSIGNED NOT NULL  →  FK entregas.id
  id_motivo  INT UNSIGNED NOT NULL  →  FK motivos_nao_conformidade.id
  descricao  VARCHAR(500) NULL
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
```

**Crie o seeder `MotivosNaoConformidadeSeeder.php`** com:

| codigo | descricao |
|--------|-----------|
| `AVARIA_PRODUTO` | Produto com avaria ou dano |
| `NAO_ENTREGUE` | Destinatário ausente |
| `ENDERECO_INCORRETO` | Endereço incorreto ou não localizado |
| `RECUSADO` | Recusado pelo destinatário |
| `EXTRAVIO` | Produto extraviado |
| `OUTROS` | Outros motivos |

**Implemente os endpoints:**

```
GET  /motivos-nao-conformidade
     → retorna lista dos motivos com ativo = 1

POST /entregas/{id}/nao-conformidades
     body: { "id_motivo": 1, "descricao": "..." }
     → registra a não conformidade
     → id_motivo obrigatório; entrega e motivo devem existir
```

---

## Commits esperados

Queremos ver o raciocínio em etapas — não um único commit com tudo.

```
fix:   correção do bug
feat:  migration motivos_nao_conformidade
feat:  migration nao_conformidades
feat:  seeder MotivosNaoConformidadeSeeder
feat:  GET /motivos-nao-conformidade
feat:  POST /entregas/{id}/nao-conformidades
docs:  BUGFIX.md preenchido
```

---

## Bônus

- `GET /rastreamento/{codigo}` — rastreamento público pelo código da entrega (ex: `BRD-2024-00001`)
- `GET /entregas/{id}/nao-conformidades` — listar NCs de uma entrega
- Docker + docker-compose funcional
- Testes automatizados

---

## Critérios de avaliação

| O que avaliamos | Peso |
|-----------------|------|
| Identificação e correção do bug | Alto |
| BUGFIX.md — clareza técnica + resposta para o time | Alto |
| Migrations corretas (FKs, índices, tipos) | Alto |
| Endpoints de não conformidade funcionando | Alto |
| Qualidade de código e organização | Médio |
| Tratamento de erro e HTTP status codes | Médio |
| Granularidade dos commits | Médio |

---

## Entrega

1. Suba em repositório **público** no GitHub (sem BRUDAM no nome)
2. README do seu projeto com: como rodar, exemplos de requisição, decisões técnicas
3. Envie ao recrutador: nome completo · link do repo · LinkedIn

---

## Dúvidas

Se algo estiver ambíguo, documente sua interpretação e siga. Decisão sob incerteza também é avaliada.

---

## Autor

**Michel Mileski** — [@eusouomichel](https://github.com/eusouomichel)
