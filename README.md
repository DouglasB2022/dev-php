# TMS API — Teste Técnico PHP Júnior

API REST em **PHP puro** para um TMS (Transportation Management System), desenvolvida como solução de um teste técnico. Este projeto contém a correção de um bug reportado pelo time de operações e a nova funcionalidade de **não conformidades** em entregas.

## Stack

- PHP 8.1+
- PDO
- MySQL 8.0 (via container Docker, imagem `mysql:8.0`)
- [Phinx](https://phinx.org) para migrations e seeds
- Composer

## Pré-requisitos

- PHP 8.1 ou superior (com a extensão `pdo_mysql`)
- Composer
- Docker

## Como rodar

### 1. Clonar o repositório e instalar as dependências

```bash
git clone https://github.com/DouglasB2022/dev-php.git
cd dev-php
composer install
```

### 2. Subir o MySQL em um container

O banco de dados roda em um container criado a partir da imagem oficial **`mysql:8.0`**:

```bash
docker run -d \
  --name mysql-tms \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=tms_test \
  -p 3306:3306 \
  mysql:8.0
```

Para conferir se o container está de pé:

```bash
docker ps
```

Para acessar o MySQL dentro do container (útil para validar migrations e seeds):

```bash
docker exec -it mysql-tms mysql -uroot -proot
```

> Ajuste o nome do container, a senha e o nome do banco conforme sua preferência. Os valores precisam ser os mesmos configurados no `.env`.

### 3. Configurar o ambiente

```bash
cp .env.example .env
```

Edite o `.env` com as credenciais do container MySQL (host, porta, banco, usuário e senha).

### 4. Criar as tabelas

```bash
vendor/bin/phinx migrate
```

### 5. Popular os dados iniciais

```bash
vendor/bin/phinx seed:run
```

### 6. Subir o servidor

```bash
php -S localhost:8000 public/index.php
```

A API ficará disponível em `http://localhost:8000`.

## Endpoints

### Já existentes

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/transportadoras` | Lista transportadoras |
| POST | `/transportadoras` | Cria transportadora |
| GET | `/transportadoras/{id}` | Detalha transportadora |
| PATCH | `/transportadoras/{id}/desativar` | Desativa transportadora |
| PATCH | `/transportadoras/{id}/reativar` | Reativa transportadora |
| GET | `/entregas` | Lista entregas |
| POST | `/entregas` | Cria entrega |
| GET | `/entregas/{id}` | Detalha entrega |
| PATCH | `/entregas/{id}/status` | Atualiza status da entrega |

### Implementados neste desafio

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/motivos-nao-conformidade` | Lista os motivos com `ativo = 1` |
| POST | `/entregas/{id}/nao-conformidades` | Registra uma não conformidade em uma entrega |

## Exemplos de requisição

### Listar motivos de não conformidade

```bash
curl http://localhost:8000/motivos-nao-conformidade
```

Resposta `200 OK` (exemplo):

```json
[
  { "id": 1, "codigo": "AVARIA_PRODUTO", "descricao": "Produto com avaria ou dano" },
  { "id": 2, "codigo": "NAO_ENTREGUE", "descricao": "Destinatário ausente" },
  { "id": 3, "codigo": "ENDERECO_INCORRETO", "descricao": "Endereço incorreto ou não localizado"},
  { "id": 4, "codigo": "RECUSADO", "descricao": "Recusado pelo destinatário"},
  { "id": 5, "codigo": "EXTRAVIO", "descricao":"Produto extraviado"},
  { "id": 6, "codigo": "OUTROS", "descricao":"Outros motivos"}
]
```

### Registrar uma não conformidade

```bash
curl -X POST http://localhost:8000/entregas/1/nao-conformidades \
  -H "Content-Type: application/json" \
  -d '{
    "id_motivo": 2,
    "descricao": "Sem acesso ao cliente"
  }'
```

Resposta `201 Created`:

```json
{
  "id": 1,
  "mensagem": "Não conformidade criada com sucesso"
}
```

O campo `descricao` é opcional; `id_motivo` é obrigatório; O `id_entrega` é informado pelo parâmetro {id} da URL

### Códigos de resposta do `POST /entregas/{id}/nao-conformidades`

| Status | Situação                         |
| ------ | -------------------------------- |
| 201    | Não conformidade registrada      |
| 403    | Motivo inativo                   |
| 404    | Entrega não encontrada           |
| 404    | Motivo não encontrado            |
| 422    | Status da entrega não permite NC |
| 422    | Motivo incompatível com o status |
| 422    | `id_motivo` obrigatório          |


### Regras para criação de não conformidades

A criação de uma não conformidade depende do status atual da entrega
e do motivo selecionado.

| Status da entrega | Motivos permitidos |
|---|---|
| SAIU_ENTREGA | AVARIA_PRODUTO, NAO_ENTREGUE, ENDERECO_INCORRETO, RECUSADO, EXTRAVIO, OUTROS |
| ENTREGUE | AVARIA_PRODUTO, EXTRAVIO, OUTROS |
| DEVOLVIDA | AVARIA_PRODUTO, ENDERECO_INCORRETO, RECUSADO, EXTRAVIO, OUTROS |
| CRIADA | Nenhum |
| COLETADA | Nenhum |
| EM_TRANSITO | Nenhum |

Além de verificar se o status permite a criação da não conformidade,
a API valida se o motivo informado é compatível com o status atual
da entrega.

## Banco de dados

### `motivos_nao_conformidade`

| Coluna | Tipo | Observações |
|--------|------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| codigo | VARCHAR(30) | UNIQUE, NOT NULL |
| descricao | VARCHAR(150) | NOT NULL |
| ativo | TINYINT(1) | NOT NULL, DEFAULT 1 |

### `nao_conformidades`

| Coluna | Tipo | Observações |
|--------|------|-------------|
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| id_entrega | INT UNSIGNED | NOT NULL, FK → `entregas.id` |
| id_motivo | INT UNSIGNED | NOT NULL, FK → `motivos_nao_conformidade.id` |
| descricao | VARCHAR(500) | NULL |
| created_at | DATETIME | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

O seeder `MotivosNaoConformidadeSeeder` popula os motivos: `AVARIA_PRODUTO`, `NAO_ENTREGUE`, `ENDERECO_INCORRETO`, `RECUSADO`, `EXTRAVIO` e `OUTROS`.

## Correção do bug

O bug reportado, a causa raiz e a resposta para o time de operações estão documentados em [`BUGFIX.md`](./BUGFIX.md).

## Decisões técnicas

- **MySQL em container:** o banco roda em um container da imagem `mysql:8.0`, o que evita instalação local e mantém o ambiente reproduzível.
- **`id_entrega` na URL:** a entrega é identificada pelo path (`/entregas/{id}/nao-conformidades`), seguindo o padrão REST de sub-recurso. O body carrega apenas `id_motivo` e `descricao`.
- **Prepared statements:** todas as consultas usam PDO com parâmetros, evitando SQL injection.
- **Conversão de tipos:** `id_entrega` e `id_motivo` são convertidos para `int` antes de irem ao banco, já que as colunas são `INT UNSIGNED`.
- **Validação antes de consulta:** campos obrigatórios são validados primeiro (`422`), depois a existência da entrega e do motivo (`404`), e só então o `INSERT`.
- **Status HTTP:** `201` para criação, `422` para dados inválidos e `404` para recursos inexistentes.
- **Commits em etapas:** o histórico segue a granularidade pedida no desafio (fix, migrations, seeder, endpoints e docs em commits separados).
- **Ambiguidades:** quando algo não estava especificado no enunciado, a interpretação adotada foi documentada aqui e no `BUGFIX.md`.

## Estrutura do projeto

```
├── db/
│   ├── migrations/
│   └── seeds/
├── public/
│   └── index.php        # ponto de entrada
├── src/
│   ├── Controllers/
│   ├── Database.php
│   └── Router.php
├── .env.example
├── BUGFIX.md
├── composer.json
└── README.md
```

> A estrutura acima é ilustrativa; ajuste conforme o layout real do seu repositório.