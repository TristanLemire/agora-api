# agora-api
Disclaimer
Ce site a été réalisé à des fins pédagogiques dans le cadre du cursus Bachelor de l’école HETIC. Les contenus présentés n'ont pas fait l'objet d'une demande de droit d'utilisation. Ce site ne sera en aucun cas exploité à des fins commerciales.

## Installing the project

After cloning the project, go to the root of the project and create a `.env.local` file:

```shell script
touch .env.local
```

---

In `.env.local` add these lines and make changes according to your configuration:

```shell script
APP_ENV=dev
DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/agoraDb?serverVersion=5.7
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
```

---

### Basic commands

Then, install dependencies:

```shell script
composer install
```

---

Create the Database:

```shell script
./bin/console doctrine:database:create
```

---

Execute the last migration:

```shell script
./bin/console doctrine:migration:migrate
```

---

Populate database with fixtures:

```shell script
./bin/console doctrine:fixtures:load
```

---

Launch the server:

```shell script
symfony server:start
```

---

### Setup JWT Token

First create the JWT Folder in config folder:
```shell script
mkdir -p config/jwt
```

---

Then, generate your `.pem` keys according to your `JWT_PASSPHRASE` located in `.env`, ie, when you will have to enter the `passphrase` enter the same value as the `JWT_PASSPHRASE`:

```shell script
openssl genrsa -out config/jwt/private.pem -aes256 4096
``` 

Same for this command:

```shell script
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

---

Now, if you try to login as an existing user like so:

#### httpie

```shell script
http POST localhost:8000/api/login_check username=noemi.levy@voila.fr password=test
```

#### CURL

```shell script
curl -X POST -H "Content-Type: application/json" localhost:8000/api/login_check -d '{"username": "noemi.levy@voila.fr", "password": "test"}'
```

You will retrieve the JWT Token:

```json
{
    "token": "eyJ0eXAi..."
}
```

With this JWT Token, you can now make request on routes `/api` of the application. You need to put the token in your `Headers` every time you want to make a request.

#### httpie-jwt

```shell script
http --auth-type=jwt --auth="eyJ0eXAi..." localhost:8000/api/users
```

#### CURL

```shell script
curl -X GET localhost:8000/api/users -H "Authorization: BEARER eyJ0eXAi..."
```

---

### Refresh JWT Token after expiration

Your JWT Token is set to expire at a certain time that is set in `config/packages/lexik_jwt_authentication.yaml`:

```yaml
# Unit is in second. So 900 seconds.
token_ttl: 900
```

If your JWT Token expire, you will need to re-generate a new one with the second token provided when you login, called `refresh_token`, the `refresh_token` can only be used once, but everytime you request a new JWT Token you will get a new `refresh_token` as well:

```json
{
    "refresh_token": "b1ca3f...",
    "token": "eyJ0eXAi..."
}
```

Retrieve a new valid JWT Token:

#### httpie

```shell script
http POST localhost:8000/api/token/refresh refresh_token=b1ca3f...
```

#### CURL

```shell script
curl -X POST -H "Content-Type: application/json" localhost:8000/api/token/refresh -d '{"refresh_token": "b1ca3f..."}'
```

With this new token, you will be able to fetch data from the API again.

---

### Swagger documentation

Visit the documentation at route: `/swagger/index.html`.

---

### Useful commands

List all routes of the API:

```shell script
./bin/console debug:router
```

---

## SQL

### MCD
![alt text](https://github.com/kentoje/agora-api/blob/master/mcd_mld/MCD.png "MCD")

### MLD
![alt text](https://github.com/kentoje/agora-api/blob/master/mcd_mld/MLD.png "MLD")
