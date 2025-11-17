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
  "username": "admin@example.com",
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

---

## 📘 Notas finales

- GraphQL endpoint: `http://localhost:8080/api/graphql`
- JWT endpoint: `http://localhost:8080/auth/login`
- Roles disponibles: `ROLE_ADMIN`, `ROLE_MERCHANT`, `ROLE_CUSTOMER`
