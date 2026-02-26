# jira-integration

Servico para criar tasks no Jira a partir de arquivos JSON.

API REST em **Symfony** com integracao Jira usando [`lesstif/php-jira-rest-client`](https://github.com/lesstif/php-jira-rest-client).

---

## PT-BR

### Requisitos

- PHP 8.1+
- Composer

### Instalacao

```bash
composer install
```

### Configuracao

Crie seu `.env` a partir do modelo e preencha as credenciais do Jira:

```bash
cp .env.sample .env
```

Valores esperados:

```dotenv
JIRA_HOST=https://your-domain.atlassian.net
JIRA_USER=your-email@your-company.com
JIRA_TOKEN=your-jira-api-token
```

> Nota: o `JIRA_TOKEN` e um API Token da Atlassian. Gere em [id.atlassian.com/manage-profile/security/api-tokens](https://id.atlassian.com/manage-profile/security/api-tokens).

### Executando localmente

```bash
php -S localhost:8000 -t public/
```

### Command de importacao para Jira

Use o comando abaixo para importar as tasks definidas em `public/JSON`:

```bash
php bin/console app:jira:import-tasks
```

#### Como o command funciona

1. Le o arquivo `public/JSON/create-order.json` e usa `create_sequence` como ordem oficial de criacao.
2. Para cada item, resolve o payload usando `payload_file` e tambem fallback para `public/JSON/issues/<external_id>.json`.
3. Le o bloco `jira_issue_input` do payload e cria a issue no Jira com:
   - `project_key`
   - `summary`
   - `issue_type`
   - `description_text`
   - `priority`
   - `labels`
4. Exibe uma tabela com `external_id`, chave Jira criada e status por item.
5. Finaliza com total de processados, sucesso e falhas.

#### Opcoes disponiveis

```bash
# valida payloads sem criar issue no Jira
php bin/console app:jira:import-tasks --dry-run

# limita quantidade de tasks processadas
php bin/console app:jira:import-tasks --limit=10

# sobrescreve o project key para todas as tasks
php bin/console app:jira:import-tasks --project-key=SEU_PROJETO

# usa outro diretorio de JSON
php bin/console app:jira:import-tasks --path=public/JSON
```

### Endpoints HTTP

#### Listar projetos

```http
GET /api/jira/projects
```

#### Criar issue

```http
POST /api/jira/issues
Content-Type: application/json

{
  "projectKey": "PROJ",
  "summary": "Titulo da tarefa",
  "description": "Descricao detalhada",
  "issueType": "Task"
}
```

#### Buscar issue

```http
GET /api/jira/issues/{issueKey}
```

Exemplo: `GET /api/jira/issues/PROJ-123`

---

## EN

### Requirements

- PHP 8.1+
- Composer

### Installation

```bash
composer install
```

### Configuration

Create your `.env` file from the template and fill in Jira credentials:

```bash
cp .env.sample .env
```

Expected values:

```dotenv
JIRA_HOST=https://your-domain.atlassian.net
JIRA_USER=your-email@your-company.com
JIRA_TOKEN=your-jira-api-token
```

> Note: `JIRA_TOKEN` is an Atlassian API token. You can generate one at [id.atlassian.com/manage-profile/security/api-tokens](https://id.atlassian.com/manage-profile/security/api-tokens).

### Running locally

```bash
php -S localhost:8000 -t public/
```

### Jira import command

Use the command below to import tasks defined in `public/JSON`:

```bash
php bin/console app:jira:import-tasks
```

#### How the command works

1. It reads `public/JSON/create-order.json` and uses `create_sequence` as the official creation order.
2. For each item, it resolves the payload using `payload_file` and also falls back to `public/JSON/issues/<external_id>.json`.
3. It reads `jira_issue_input` from each payload and creates a Jira issue with:
   - `project_key`
   - `summary`
   - `issue_type`
   - `description_text`
   - `priority`
   - `labels`
4. It prints a table with `external_id`, created Jira key, and per-item status.
5. It finishes with totals for processed, success, and failures.

#### Available options

```bash
# validate payloads without creating Jira issues
php bin/console app:jira:import-tasks --dry-run

# limit number of processed tasks
php bin/console app:jira:import-tasks --limit=10

# override project key for all imported tasks
php bin/console app:jira:import-tasks --project-key=YOUR_PROJECT

# use a different JSON directory
php bin/console app:jira:import-tasks --path=public/JSON
```

### HTTP endpoints

#### List projects

```http
GET /api/jira/projects
```

#### Create issue

```http
POST /api/jira/issues
Content-Type: application/json

{
  "projectKey": "PROJ",
  "summary": "Task title",
  "description": "Detailed description",
  "issueType": "Task"
}
```

#### Get issue

```http
GET /api/jira/issues/{issueKey}
```

Example: `GET /api/jira/issues/PROJ-123`
