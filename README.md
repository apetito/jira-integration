# jira-integration

Serviço para criar tasks no Jira a partir de arquivos JSON.

Uma API REST construída com **Symfony 6.4** que integra com a API do Jira usando a biblioteca [`lesstif/php-jira-rest-client`](https://github.com/lesstif/php-jira-rest-client).

## Requisitos

- PHP 8.1+
- Composer

## Instalação

```bash
composer install
```

## Configuração

Copie o arquivo `.env` e ajuste as variáveis de ambiente com suas credenciais do Jira:

```bash
cp .env .env.local
```

Edite `.env.local`:

```
JIRA_HOST=https://your-domain.atlassian.net
JIRA_USER=your-email@example.com
JIRA_TOKEN=your-jira-api-token
```

> **Nota:** O `JIRA_TOKEN` é um token de API do Jira. Você pode gerar um em [id.atlassian.com/manage-profile/security/api-tokens](https://id.atlassian.com/manage-profile/security/api-tokens).

## Executando

```bash
php -S localhost:8000 -t public/
```

## Endpoints

### Listar projetos

```
GET /api/jira/projects
```

### Criar issue

```
POST /api/jira/issues
Content-Type: application/json

{
    "projectKey": "PROJ",
    "summary": "Título da tarefa",
    "description": "Descrição detalhada",
    "issueType": "Task"
}
```

### Buscar issue

```
GET /api/jira/issues/{issueKey}
```

Exemplo: `GET /api/jira/issues/PROJ-123`
