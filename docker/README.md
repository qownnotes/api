# Running with Docker

This is simple docker compose setup using docker with php-fpm and nginx to serve
the symfony app.

## Running the Server

- `docker compose up -d` to start the server in the background
- `docker compose up` to start the server in the foreground with logs
- `docker compose stop` to stop the server
- `docker compose logs -f` to see the logs

## Executing Symfony commands

- `docker compose run php-fpm bash` to start a shell in the php-fpm container.
- After this you can run symfony commands like: `./bin/console debug:config`
- You can also call `bin/console` commands directly from outside the container with `./console debug:config`

## Links

- Open <http://127.0.0.1:8000/> for the API Platform webpage
