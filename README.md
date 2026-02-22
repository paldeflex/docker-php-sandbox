# PHP Sandbox

Docker-окружение для изучения PHP и SQL. Включает PHP 8.5, MySQL 8.4, Nginx, Xdebug и Composer.

## Установка

Скопируйте файл окружения и при необходимости отредактируйте его:

```bash
cp .env.example .env
```

Запустите контейнеры:

```bash
docker compose up -d
```

Установите зависимости Composer:

```bash
docker compose exec php composer install
```

Откройте в браузере: [http://localhost](http://localhost)

Если порт 80 занят, измените `NGINX_PORT` в `.env` и откройте `http://localhost:<порт>`.

## Переменные окружения

| Переменная            | По умолчанию             | Описание                    |
|-----------------------|--------------------------|-----------------------------|
| `NGINX_PORT`          | `80`                     | Порт Nginx на хосте         |
| `MYSQL_PORT`          | `3306`                   | Порт MySQL на хосте         |
| `MYSQL_ROOT_PASSWORD` | `root_pass`              | Пароль root для MySQL       |
| `MYSQL_DATABASE`      | `db_name`                | Имя базы данных             |
| `MYSQL_USER`          | `app_user`               | Пользователь MySQL          |
| `MYSQL_PASSWORD`      | `app_pass`               | Пароль пользователя MySQL   |
| `XDEBUG_MODE`        | `debug`                  | Режим Xdebug                |
| `XDEBUG_CLIENT_HOST` | `host.docker.internal`   | Хост для подключения Xdebug |
| `XDEBUG_CLIENT_PORT` | `9003`                   | Порт Xdebug                 |

## Остановка

```bash
docker compose down
```