# Wallets API

[![CI](https://github.com/pedromeirellesc/wallets/actions/workflows/ci.yml/badge.svg)](https://github.com/pedromeirellesc/wallets/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/pedromeirellesc/wallets/branch/main/graph/badge.svg)](https://codecov.io/gh/pedromeirellesc/wallets)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

API para gerenciamento de carteiras desenvolvida com PHP sem uso de frameworks, com base em fundamentos de Clean Architecture, DDD e SOLID e desenvolvida orientada a testes (TDD).

## Requisitos

- PHP 8.3+
- MySQL 8.0+

## Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/pedromeirellesc/wallets.git
cd wallets
```

### 2. Instale as dependências

```bash
composer install
```

### 3. Configure o ambiente

```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações de banco de dados:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wallets
DB_USERNAME=db_username
DB_PASSWORD=db_password
```
### 4. Rode os testes

```bash
./vendor/bin/phpunit
```

Edite o arquivo `.env` com suas configurações de banco de dados:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wallets
DB_USERNAME=db_username
DB_PASSWORD=db_password
```

### 4. Inicie o servidor

```bash
php -S localhost:8000 -t public
```

A aplicação estará disponível em `http://localhost:8000`

## Endpoints

### Auth
- `POST /api/v1/users/register` - Registrar novo usuário
- `POST /api/v1/users/login` - Login e geração de token JWT

### Wallets
- `GET /api/v1/wallets` - Listar todas as carteiras
- `GET /api/v1/wallets/{uuid}` - Detalhes de uma carteira

### Transactions
- `POST /api/v1/wallets/{uuid}/deposit` - Realizar depósito
- `POST /api/v1/wallets/{uuid}/withdraw` - Realizar saque
- `POST /api/v1/transactions/transfer` - Realizar transferência entre duas Wallets