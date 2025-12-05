# README

## DOCKER DEPLOY DEV
- docker-compose -f docker-compose.dev.yml up -d --build

## SYMFONY DEPLOY DEV
### Install composer dependences
- docker exec -it veci_php sh
- composer install

### Configure JWT
- docker exec -it veci_php sh
- php bin/console lexik:jwt:generate-keypair

## SYMFONY WORK DEV
- docker exec -it veci_php sh
- install: npm i
- build: npm run dev
- watch: npm run watch

API

# 🧩 API GraphQL con Symfony + API Platform + JWT

## 🚀 Resumen del proyecto
La API está construida con **Symfony 7**, **API Platform** y **GraphQL**, y utiliza **LexikJWTAuthenticationBundle** para autenticación basada en tokens JWT.

El proyecto expone endpoints GraphQL y REST (API Platform) bajo `/api/`.
La autenticación se realiza con JWT emitidos desde el endpoint `/auth/login`.

---

## 🔑 Autenticación

### 1️⃣ Generar token de acceso

**Endpoint:**
`POST http://localhost:8080/auth/login`

**Request body:**
```json
{
  "email": "admin@example.com",
  "password": "your_password"
}
```

**Response:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5..."
}
```

**Uso posterior:**
El token debe enviarse en **Authorization header** para cualquier consulta protegida:
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5...
```

---

## 🧠 Estructura GraphQL

**Endpoint:**
`POST http://localhost:8080/api/graphql`
O en navegador (modo playground):
`http://localhost:8080/api/graphql`

### 2️⃣ Query personalizada `me`
Devuelve el usuario autenticado según el JWT.

**Query:**
```graphql
query {
  meUser {
    id
    email
    roles
    customer {
      id
    }
    profile{
        firstName
    }
    merchant {
      id
    }
  }
}
```

**Respuesta:**
```json
{
  "data": {
    "meUser": {
      "id": "/api/users/7",
      "email": "customer@example.com",
      "roles": ["ROLE_CUSTOMER"],

    }
  }
}
```

> ✅ No requiere parámetro `id`.
> 🔒 Necesita JWT válido en el header.

---

### 2️⃣ Mutation personalizada `registerCustomerUser`
Devuelve el usuario creado y envia email de verificación para ingresar por la app. Tambien cuenta con el jwt token para no tener q volver a consultarlo. Ahora se agrega el refreshToken unico por sesion de user.

**Mutation:**
```graphql
mutation {
  registerCustomerUser(
    input: {
      email: "cliente@nuevocustomer6.com"
      password: "supersecreto"
      firstname: "Ana"
      lastname: "Gómez"
    }
  ) {
    user {
      id
      email
      roles
      customer {
        id
      }
      profile {
        firstName
        lastName
      }
      jwt
      refreshToken
    }
  }
}
```

**Respuesta:**
```json
{
  "data": {
    "registerCustomerUser": {
      "user": {
        "id": "/api/users/14",
        "email": "cliente@nuevocustomer6.com",
        "roles": [
          "ROLE_CUSTOMER"
        ],
        "customer": {
          "id": "/api/customers/11"
        },
        "profile": {
          "firstName": "Ana",
          "lastName": "Gómez"
        },
        "jwt": "asdasdasd12312312"
      }
    }
  }
}
```

> ✅ No requiere parámetro `id`.
> Requiere la config en .symfony.env del deep link -> APP_VERIFY_URL=https://app.veci.com/verify-email

---

### 2️⃣ Mutation personalizada `requestPasswordResetAuth`
Devuelve el success y mensjae, envia email de reset de contraseña para ingresar por la app.

**Mutation:**
```graphql
mutation {
  requestPasswordResetAuth(input: {
    email: "cliente@customer.com"
  }) {
    auth {
      success
      message
    }
  }
}
```

**Respuesta:**
```json
{
  "data": {
    "requestPasswordResetAuth": {
      "auth": {
        "success": true,
        "message": "Si el email existe, se envió un link para modificar la contraseña."
      }
    }
  }
}
```

> ✅ No requiere parámetro `id`.
> Requiere la config en .symfony.env del deep link-> APP_PASSWORD_RESET_URL=https://app.veci.com/reset-password
> En casos de error devolverá el success: false y el mensaje del error.

---

### 2️⃣ Mutation personalizada `resetPasswordAuth`
Desde el correo que recibio el token debe enviar el token y password nuevo. Obtendrás el mensaje de success y ademas token y refreshToken para evitar doble llamada.

**Mutation:**
```graphql
mutation {
  resetPasswordAuth(input:{
    token: "GaXUhubMveTjHDwsH...",
    password: "newSecret1234"
  }) {
    auth {
      success
      message
      jwt
      refreshToken
    }
  }
}
```

**Respuesta:**
```json
{
  "data": {
    "resetPasswordAuth": {
      "auth": {
        "success": true,
        "message": "Password updated successfully.",
        "jwt": "eyJ0eXAiOiJKV1QiLCJhbGciO...",
        "refreshToken": "dfde4d0487377719ba79a4ad93cce1..."
      }
    }
  }
}
```

> ✅ No requiere parámetro `id`.
> En casos de error devolverá el success: false y el mensaje del error.

## 🧑‍💼 Entidad `User`

### Operaciones disponibles (GraphQL)
En la doc de playground para dev
http://localhost:8080/docs/graphql_playground

---

## ⚙️ Config de JWT

Las claves están en `config/jwt/`
```
private.pem
public.pem
```

Y el archivo `.env` debe tener:
```
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=tu_passphrase
```

---

## ✅ Resumen del flujo de trabajo

1. El frontend envía `POST /auth/login` con email y password.
2. Recibe un token JWT.
3. Lo usa en `Authorization: Bearer ...` para consultas protegidas.
4. Puede ejecutar `meUser` para obtener su propio perfil. Enviando la Authorization correspondiente

Ahora cuenta con LoginCustomerUser a traves de graphQL que devuelve la información completa con token y refresh incluido como el register.

mutation {
  registerCustomerUser(
    input: {
      email: "cliente@nuevocustomer12.com"
      password: "supersecreto"
      firstname: "Ana"
      lastname: "Gómez"
    }
  ) {
    user {
      id
      email
      roles
      customer {
        id
      }
      profile {
        firstName
        lastName
      }
      jwt
      refreshToken
    }
  }
}

**Respuesta:**
```json
{
  "data": {
    "registerCustomerUser": {
      "user": {
        "id": "/api/users/20",
        "email": "cliente@nuevocustomer12.com",
        "roles": [
          "ROLE_CUSTOMER"
        ],
        "customer": {
          "id": "/api/customers/17"
        },
        "profile": {
          "firstName": "Ana",
          "lastName": "Gómez"
        },
        "jwt": "eyJ0eXAiOiJK...",
        "refreshToken": "2c93d4d82d0..."
      }
    }
  }
}
```

---

## 📘 Notas finales

- GraphQL endpoint: `http://localhost:8080/api/graphql`
- JWT endpoint: `http://localhost:8080/auth/login`
- Roles disponibles: `ROLE_ADMIN`, `ROLE_MERCHANT`, `ROLE_CUSTOMER`
