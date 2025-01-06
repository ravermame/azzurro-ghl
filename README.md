### .env setup

- cp .env.example .env [If not having a ".env" file]

- Updates database values

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=azzurro_app
DB_USERNAME=user
DB_PASSWORD=secret

## Installation

- docker-compose up --build -d

- docker-compose exec app composer install

- docker-compose exec app php artisan key:generate

- docker-compose exec app php artisan migrate

- docker-compose down [Only if you change anything in docker settings]

- chmod -R 777 storage/