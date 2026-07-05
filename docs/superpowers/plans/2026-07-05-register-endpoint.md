# Register Endpoint Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an anonymous `POST /api/register` endpoint that creates a user with a hashed password and returns `201` with a minimal user representation.

**Architecture:** A plain `RegistrationController` (mirrors the existing `SecurityController` login stub). It decodes JSON, validates raw input, hashes the password, persists a `User`, and returns a hand-built JSON array — never the serialized entity. Duplicate emails are guarded by both a `UniqueEntity` constraint and a DB unique-index catch. The route is opened to anonymous access in `security.yaml`.

**Tech Stack:** PHP 8.4, Symfony 8.0, API Platform 4.2, Doctrine ORM 3.6, PostgreSQL 17, PHPUnit (via `symfony/test-pack`).

## Global Constraints

- PHP `>=8.4`; Symfony components `8.0.*`.
- Database is PostgreSQL 17 (local: Dockerized `acroyoga`, user `acro_user`). Test DB: `acroyoga_test`.
- Commit messages: NO `Co-Authored-By` and NO AI-attribution trailers.
- Response from `/api/register` MUST be a hand-built array `{id, email, displayName}` — never the serialized `User` (no `password`/`roles` leakage).
- Password is validated on the **raw plaintext input before hashing**, never on `User::$password`.
- Password rule for v1: `NotBlank` + `Length(min: 8)` only (intentionally minimal; tightened later).
- The success test MUST run with **no `Authorization` header** (tokenless) to prove the firewall/access_control path.
- Test schema is built via `doctrine:schema:create --env=test` (NOT `migrations:migrate` — existing migration is SQLite-dialect).

---

## File Structure

- `api/src/Controller/RegistrationController.php` — **new.** The endpoint: decode, validate, hash, persist, respond. Single responsibility.
- `api/src/Entity/User.php` — **modify.** Add `#[UniqueEntity(fields: ['email'])]`.
- `api/config/packages/security.yaml` — **modify.** Add the `^/api/register$` PUBLIC_ACCESS rule.
- `api/.env.test` — **new/modify.** Add `DATABASE_URL` for the Postgres test DB.
- `api/tests/Functional/RegistrationControllerTest.php` — **new.** Functional tests.
- `api/phpunit.dist.xml` — **new** (from `symfony/test-pack` recipe).

---

## Task 1: Add UniqueEntity constraint to User + open the route

**Files:**
- Modify: `api/src/Entity/User.php`
- Modify: `api/config/packages/security.yaml`

**Interfaces:**
- Consumes: existing `User` entity, existing `security.yaml`.
- Produces: `User` now carries `#[UniqueEntity(fields: ['email'], message: 'This email is already registered.')]`; `^/api/register$` is PUBLIC_ACCESS. Task 2's controller relies on both.

This task has no unit test of its own — its behavior is proven by Task 3's functional tests. It is committed separately because it is a self-contained, reviewable config/annotation change.

- [ ] **Step 1: Add the UniqueEntity import to `User.php`**

Add this `use` statement alongside the existing imports (after the `Symfony\Component\Validator\Constraints as Assert;` line):

```php
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
```

- [ ] **Step 2: Add the UniqueEntity attribute to the User class**

Add the attribute directly above the `class User` declaration, together with the existing attributes:

```php
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ApiResource]
#[UniqueEntity(fields: ['email'], message: 'This email is already registered.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
```

- [ ] **Step 3: Open the register route in `security.yaml`**

In `api/config/packages/security.yaml`, add the register rule to `access_control` **before** the two existing `^/api` rules. The block becomes:

```yaml
    access_control:
        - { path: ^/api/login$, roles: PUBLIC_ACCESS }
        - { path: ^/api/register$, roles: PUBLIC_ACCESS }
        - { path: ^/api/docs, roles: PUBLIC_ACCESS }
        - { path: ^/api$, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

- [ ] **Step 4: Verify config is valid**

Run: `php bin/console lint:yaml config/packages/security.yaml`
Expected: `All 1 YAML files contain valid syntax.`
(If PHP/DB drivers are unavailable in the shell, at minimum confirm the YAML lints; the firewall path is fully exercised by Task 3.)

- [ ] **Step 5: Commit**

```bash
git add api/src/Entity/User.php api/config/packages/security.yaml
git commit -m "feat: add UniqueEntity on User email and open /api/register route"
```

---

## Task 2: RegistrationController

**Files:**
- Create: `api/src/Controller/RegistrationController.php`

**Interfaces:**
- Consumes: `App\Entity\User` (setters `setEmail`, `setDisplayName`, `setPassword`, getters `getId`, `getEmail`, `getDisplayName`); `#[UniqueEntity]` on `User` from Task 1; `security.yaml` PUBLIC_ACCESS rule from Task 1.
- Produces: route `POST /api/register` (name `api_register`) returning `201` `{id, email, displayName}` on success, `400` on bad JSON, `422` `{errors: {field: message}}` on validation failure.

This task's controller is verified end-to-end by Task 3's functional tests (they are written first, in Task 3). Implement the controller here so Task 3 can turn green.

- [ ] **Step 1: Create the controller file**

Create `api/src/Controller/RegistrationController.php` with exactly this content:

```php
<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $displayName = $data['displayName'] ?? null;

        // Validate raw input (password checked here, BEFORE hashing).
        $violations = $validator->validate(
            ['email' => $email, 'password' => $password, 'displayName' => $displayName],
            new Assert\Collection([
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'password' => [new Assert\NotBlank(), new Assert\Length(min: 8)],
                'displayName' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            ])
        );
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setDisplayName($displayName);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        // Entity-level validation (fires UniqueEntity on email).
        $entityViolations = $validator->validate($user);
        if (count($entityViolations) > 0) {
            return $this->json(['errors' => $this->formatViolations($entityViolations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $em->persist($user);
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            // Race-condition fallback: another request registered this email between validate() and flush().
            return $this->json(
                ['errors' => ['email' => 'This email is already registered.']],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
        ], Response::HTTP_CREATED);
    }

    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $field = trim($violation->getPropertyPath(), '[]');
            $errors[$field] = $violation->getMessage();
        }

        return $errors;
    }
}
```

- [ ] **Step 2: Verify the route is registered**

Run: `php bin/console debug:router api_register`
Expected: a table showing `POST` method and path `/api/register`.
(If the shell lacks DB drivers, this command still works — it does not touch the database. If PHP itself is unavailable, defer verification to Task 3.)

- [ ] **Step 3: Commit**

```bash
git add api/src/Controller/RegistrationController.php
git commit -m "feat: add POST /api/register controller"
```

---

## Task 3: Functional tests + test harness

**Files:**
- Create: `api/tests/Functional/RegistrationControllerTest.php`
- Create: `api/phpunit.dist.xml` (via `symfony/test-pack` recipe)
- Modify/Create: `api/.env.test`

**Interfaces:**
- Consumes: `POST /api/register` from Task 2; `User` entity + repository; `acroyoga_test` Postgres DB.
- Produces: a passing functional test suite covering success + four failure modes.

**Environment prerequisite:** Docker Postgres running (`docker compose up -d db` from repo root) and a PHP with `pdo_pgsql` enabled. If the current shell lacks these, run this task in the real dev environment.

- [ ] **Step 1: Install the test pack**

Run from `api/`:
```bash
composer require --dev symfony/test-pack
```
Expected: installs `phpunit/phpunit`, `symfony/phpunit-bridge`, `symfony/browser-kit`, `symfony/css-selector`; creates `phpunit.dist.xml` and a `tests/` directory.

- [ ] **Step 2: Point the test env at Postgres**

Ensure `api/.env.test` contains a `DATABASE_URL` line (add it if the recipe didn't). Use the same local Docker credentials as dev — the `when@test` doctrine config appends the `_test` suffix, yielding `acroyoga_test`:

```dotenv
DATABASE_URL="postgresql://acro_user:local_dev_password@127.0.0.1:5432/acroyoga?serverVersion=17&charset=utf8"
```

- [ ] **Step 3: Create the test database schema (from entity metadata)**

Run from `api/`:
```bash
php bin/console --env=test doctrine:database:create --if-not-exists
php bin/console --env=test doctrine:schema:create
```
Expected: `Database "acroyoga_test" ... created` (or already exists), then `Database schema created successfully!`.
Note: uses `schema:create`, NOT `migrations:migrate` — the existing migration is SQLite-dialect and will not replay on Postgres.

- [ ] **Step 4: Write the failing test**

Create `api/tests/Functional/RegistrationControllerTest.php` with exactly this content:

```php
<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationControllerTest extends WebTestCase
{
    private function post(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $client = static::getClient();
        $client->request(
            'POST',
            '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        return $client->getResponse();
    }

    protected function setUp(): void
    {
        self::createClient();
        // Clean the user table so each test starts from a known state.
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    public function testRegistersNewUserTokenless(): void
    {
        // No Authorization header is ever set — proves the firewall/access_control path.
        $response = $this->post([
            'email' => 'new@example.com',
            'password' => 'password123',
            'displayName' => 'New User',
        ]);

        self::assertSame(201, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        // Response contains exactly these keys — no password, no roles.
        self::assertSame(['id', 'email', 'displayName'], array_keys($body));
        self::assertSame('new@example.com', $body['email']);
        self::assertSame('New User', $body['displayName']);
        self::assertIsInt($body['id']);

        // The persisted password is hashed, not the plaintext.
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'new@example.com']);
        self::assertInstanceOf(User::class, $user);
        self::assertNotSame('password123', $user->getPassword());
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user, 'password123'));
    }

    public function testRejectsDuplicateEmail(): void
    {
        $payload = ['email' => 'dup@example.com', 'password' => 'password123', 'displayName' => 'First'];
        self::assertSame(201, $this->post($payload)->getStatusCode());

        $response = $this->post($payload);
        self::assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('email', $body['errors']);
    }

    public function testRejectsShortPassword(): void
    {
        // Guards the "password validated on the hash" silent-failure risk.
        $response = $this->post([
            'email' => 'short@example.com',
            'password' => 'x',
            'displayName' => 'Short Pw',
        ]);
        self::assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('password', $body['errors']);
    }

    public function testRejectsInvalidEmail(): void
    {
        $response = $this->post([
            'email' => 'not-an-email',
            'password' => 'password123',
            'displayName' => 'Bad Email',
        ]);
        self::assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('email', $body['errors']);
    }

    public function testRejectsBlankDisplayName(): void
    {
        $response = $this->post([
            'email' => 'noname@example.com',
            'password' => 'password123',
            'displayName' => '',
        ]);
        self::assertSame(422, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        self::assertArrayHasKey('displayName', $body['errors']);
    }
}
```

- [ ] **Step 5: Run the tests to verify they FAIL first (if the controller were absent)**

Since Task 2 already implemented the controller, run the suite and expect PASS. If executing tasks strictly in order with the controller not yet present, this step would FAIL with 404 on `/api/register` — that is the expected red state.

Run from `api/`:
```bash
php bin/phpunit tests/Functional/RegistrationControllerTest.php
```

- [ ] **Step 6: Run the tests to verify they PASS**

Run from `api/`:
```bash
php bin/phpunit tests/Functional/RegistrationControllerTest.php
```
Expected: `OK (5 tests, ...)`.

Troubleshooting if red:
- `could not find driver` → PHP lacks `pdo_pgsql`; enable the extension.
- Connection refused → `docker compose up -d db` from the repo root.
- 401 instead of 201/422 → the `api` firewall is rejecting the anonymous request; extend the `login` firewall `pattern` to `^/api/(login|register)` (or add a dedicated public firewall) and re-run.
- `relation "user" does not exist` → re-run Step 3 (`schema:create`).

- [ ] **Step 7: Commit**

```bash
git add api/tests/Functional/RegistrationControllerTest.php api/phpunit.dist.xml api/.env.test api/composer.json api/composer.lock
git commit -m "test: add functional tests for /api/register"
```

---

## Task 4: Update roadmap

**Files:**
- Modify: `ROADMAP.md`

**Interfaces:** none (documentation only).

- [ ] **Step 1: Mark the register item done and record the migration follow-up**

In `ROADMAP.md`, under `## Immediate`, change the register line to done and move it to `## Done`; add a new item capturing the discovered migration-dialect issue. Concretely:

- Move `Register endpoint: POST /api/register — create user with hashed password` into the `## Done` list (as a completed bullet).
- Add under `## Mid-Term` (or a suitable section):
  `- [ ] Regenerate migrations for PostgreSQL — the initial migration (Version20260621182226) is SQLite-dialect; regenerate from entity metadata so migrations:migrate works on Postgres`

- [ ] **Step 2: Commit**

```bash
git add ROADMAP.md
git commit -m "docs: mark register endpoint done, note Postgres migration follow-up"
```

---

## Self-Review

**Spec coverage:**
- Route + PUBLIC_ACCESS → Task 1.
- Hand-built response array → Task 2 Step 1 + asserted in Task 3 (`array_keys` check).
- Password validated pre-hash → Task 2 (Collection on raw input) + Task 3 `testRejectsShortPassword`.
- Duplicate email two layers (UniqueEntity + DB catch) → Task 1 attribute + Task 2 try/catch + Task 3 `testRejectsDuplicateEmail`.
- Tokenless success test → Task 3 `testRegistersNewUserTokenless`.
- 400 on bad JSON → Task 2 Step 1 (covered in code; not separately tested — acceptable, low risk).
- Test DB via schema:create → Task 3 Step 3.
- displayName required, max 100 → Task 2 + Task 3 `testRejectsBlankDisplayName`.

**Placeholder scan:** none — all steps contain concrete code/commands.

**Type consistency:** `formatViolations()` defined and used in Task 2; response keys `{id, email, displayName}` consistent between Task 2 code and Task 3 assertions; `DATABASE_URL` DB name consistent (`acroyoga` + `_test` suffix → `acroyoga_test`).
