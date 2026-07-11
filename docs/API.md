# TramSangTao Mail — API Reference (Agent-Friendly)

> Disposable / temporary email inbox. Self-hosted Symfony app.
> This file is optimized for LLM agents: dense, structured, machine-parsable. One endpoint per section. All paths are relative to the host (e.g. `https://tempfastmail.com`).

---

## 0. Conventions

- **Base URL:** `{host}` — set to your deployment, e.g. `https://tempfastmail.com`.
- **Content type:** `application/json` for all POST request bodies. Responses are JSON unless noted.
- **Auth:** Only the inbound webhook (`POST /api/email`) requires auth. All other endpoints are public.
- **Auth header (webhook only):** `Authorization: <CREATE_RECEIVED_EMAIL_API_AUTHORIZATION_KEY>` — raw value, **no** `Bearer ` prefix. Compared with strict equality against the env var.
- **UUID format:** UUIDv7 string, e.g. `0193e0a9-1a0b-7b9b-a0f1-9f1c8a2c8c4d`.
- **Timestamps:** ISO-8601 with timezone, e.g. `2026-05-15T10:30:00+00:00`.
- **Mailbox lifetime:** Received emails auto-delete after 48 hours.
- **Domain:** Mailbox domain is fixed to the first active domain configured in admin (you don't pick it via API).

---

## 1. Endpoint Index

| # | Method | Path | Auth | Purpose |
|---|--------|------|------|---------|
| 1 | POST | `/api/email-box` | no | Create random mailbox |
| 2 | POST | `/api/email-box/custom` | no | Find-or-create named mailbox |
| 3 | POST | `/api/email-box/validate` | no | Check `(email,uuid)` pair still exists |
| 4 | GET  | `/api/email-box/{emailBoxUuid}/emails` | no | List messages in mailbox (marks all as read) |
| 5 | GET  | `/api/email-box/{emailBoxUuid}/email/{emailUuid}` | no | Get one full message (marks as read) |
| 6 | GET  | `/email-box/{emailBoxUuid}/email/{emailUuid}` | no | Render one message as HTML page |
| 7 | POST | `/api/email` | **yes** | Webhook: ingest incoming email (called by SMTP gateway) |

---

## 2. POST /api/email-box — Create random mailbox

Creates a brand-new mailbox with a randomly generated local part on the default domain.

**Request body:** none.

**Response 200 — `application/json`:**
```json
{
  "email": "fluffy.kitten.4821@example.com",
  "uuid":  "0193e0a9-1a0b-7b9b-a0f1-9f1c8a2c8c4d"
}
```

**cURL:**
```bash
curl -X POST {host}/api/email-box
```

**Notes:**
- Client IP and Cloudflare country code (`CF-IPCountry` header) are recorded server-side.
- Keep the `uuid` — it's the only credential for reading messages later.

---

## 3. POST /api/email-box/custom — Find-or-create named mailbox

Returns the existing mailbox with that custom name if it exists, otherwise creates it. Idempotent on `name`.

**Request body:**
```json
{ "name": "team-alpha" }
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `name` | string | yes | 1–64 chars, regex `^[A-Za-z0-9](?:[A-Za-z0-9._-]*[A-Za-z0-9])?$` — letters / digits / `.` / `_` / `-`; must start AND end with alphanumeric. |

**Response 200 — `application/json`:** same shape as endpoint #2.

**cURL:**
```bash
curl -X POST {host}/api/email-box/custom \
  -H 'Content-Type: application/json' \
  -d '{"name":"team-alpha"}'
```

**Errors:** `422 Unprocessable Entity` if `name` fails validation.

---

## 4. POST /api/email-box/validate — Validate (email, uuid)

Used to check whether a mailbox a client cached in localStorage still exists on the server.

**Request body:**
```json
{
  "email": "team-alpha@example.com",
  "uuid":  "0193e0a9-1a0b-7b9b-a0f1-9f1c8a2c8c4d"
}
```

**Response 200 — `application/json`:**
```json
{ "is_valid": true }
```

`is_valid` is `false` if no mailbox matches BOTH the email and the uuid. This is a lookup, not a security check — it leaks existence.

**cURL:**
```bash
curl -X POST {host}/api/email-box/validate \
  -H 'Content-Type: application/json' \
  -d '{"email":"team-alpha@example.com","uuid":"<uuid>"}'
```

---

## 5. GET /api/email-box/{emailBoxUuid}/emails — List messages

Lists all messages currently stored for a mailbox.

**Side effects:**
- Updates the mailbox's `last_accessed_at`.
- Marks the *subject preview* of all listed emails as read (does **not** mark body-read; that happens in #6).

**Response 200 — `application/json`:** array of:
```json
[
  {
    "uuid":        "0193e0a9-1a0b-7b9b-a0f1-9f1c8a2c8c4d",
    "from":        "sender@partner.com",
    "real_to":     "team-alpha@example.com",
    "from_name":   "Sender",
    "subject":     "Welcome!",
    "received_at": "2026-05-15T10:30:00+00:00"
  }
]
```

| Field | Type | Notes |
|-------|------|-------|
| `uuid` | string | Message uuid — use with endpoint #5. |
| `from` | string | Falls back to `real_from` if `from_address` is null. |
| `real_to` | string | The mailbox address that received this message. |
| `from_name` | string \| null | Display name. |
| `subject` | string | `"(no subject)"` when missing. |
| `received_at` | ISO-8601 | When the webhook ingested it. |

**cURL:**
```bash
curl {host}/api/email-box/<emailBoxUuid>/emails
```

**Errors:** `404` if mailbox uuid does not exist.

---

## 6. GET /api/email-box/{emailBoxUuid}/email/{emailUuid} — Get one message

Returns the full message including HTML body. Marks the message as read.

**Response 200 — `application/json`:**
```json
{
  "uuid":        "0193e0a9-1a0b-7b9b-a0f1-9f1c8a2c8c4d",
  "from":        "sender@partner.com",
  "real_to":     "team-alpha@example.com",
  "from_name":   "Sender",
  "subject":     "Welcome!",
  "html":        "<p>Hello!</p>",
  "received_at": "2026-05-15T10:30:00+00:00"
}
```

Same fields as #4 plus `html` (string | null — raw HTML body, may be untrusted; sandbox before rendering).

**cURL:**
```bash
curl {host}/api/email-box/<emailBoxUuid>/email/<emailUuid>
```

**Errors:** `404` if mailbox or message not found, or if the message does not belong to that mailbox.

---

## 7. GET /email-box/{emailBoxUuid}/email/{emailUuid} — Render HTML page

Not a JSON endpoint. Returns a full Twig-rendered HTML page (used by the "View Full Screen" button in the UI). Useful when you want to display a message in a sandboxed iframe.

**Response 200 — `text/html`:** complete HTML document.

**cURL:**
```bash
curl {host}/email-box/<emailBoxUuid>/email/<emailUuid>
```

---

## 8. POST /api/email — Inbound webhook (auth required)

Called by the upstream SMTP gateway (e.g. the Cloudflare Email Worker described in `docs/CLOUDFLARE_EMAIL_WORKER_SETUP.md`). Ingests one incoming email.

**Headers:**
```
Authorization: <CREATE_RECEIVED_EMAIL_API_AUTHORIZATION_KEY>
Content-Type:  application/json
```

The value is the literal env var, with **no** `Bearer ` prefix. Wrong/missing header → `UnauthorizedToCreateReceivedEmailException` (HTTP 500 by default unless mapped).

**Request body:**
```json
{
  "real_from":    "sender@partner.com",
  "real_to":      "team-alpha@example.com",
  "subject":      "Welcome!",
  "from_name":    "Sender",
  "from_address": "sender@partner.com",
  "to_multiple":  ["team-alpha@example.com"],
  "bcc_multiple": null,
  "html":         "<p>Hello!</p>",
  "metadata":     null
}
```

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `real_from` | string (email) | yes | Envelope sender. `Assert\Email`, `NotBlank`. |
| `real_to` | string (email) | yes | Envelope recipient. Used to look up the target mailbox. `Assert\Email`, `NotBlank`. |
| `subject` | string \| null | no | |
| `from_name` | string \| null | no | Display name parsed from `From:`. |
| `from_address` | string \| null | no | Address parsed from `From:` header (often equals `real_from`). |
| `to_multiple` | string[] \| null | no | All `To:` recipients. |
| `bcc_multiple` | string[] \| null | no | All `Bcc:` recipients. |
| `html` | string \| null | no | Full HTML body. |
| `metadata` | object \| null | no | Free-form. |

**Response 201 — `application/json`:**
```json
"OK"
```

**cURL:**
```bash
curl -X POST {host}/api/email \
  -H 'Content-Type: application/json' \
  -H 'Authorization: <CREATE_RECEIVED_EMAIL_API_AUTHORIZATION_KEY>' \
  -d '{"real_from":"s@p.com","real_to":"team-alpha@example.com","subject":"Hi","from_name":null,"from_address":null,"to_multiple":null,"bcc_multiple":null,"html":"<p>hi</p>","metadata":null}'
```

**Behavior:**
- If no mailbox matches `real_to`, the message is silently dropped (mailbox must exist first).
- Stored emails are auto-deleted after 48 hours by `DeleteOldEmailsCommand`.

---

## 9. Typical Agent Workflow

End-to-end "give me a mailbox, then poll for the verification email":

```text
1. POST /api/email-box                     → save  {email, uuid}
2. Hand `email` to whatever needs it (signup form, etc.)
3. Loop every N seconds:
     GET /api/email-box/{uuid}/emails      → array of messages
   Stop when the expected sender appears.
4. GET /api/email-box/{uuid}/email/{emailUuid}
                                           → read `html`, extract code / link
```

Named-mailbox variant: replace step 1 with `POST /api/email-box/custom {"name": "..."}` — same name → same mailbox across sessions.

---

## 10. Error Shape

Symfony default JSON error response on validation failure (`422`):
```json
{
  "type":   "https://symfony.com/errors/validation",
  "title":  "Validation Failed",
  "detail": "name: Name may contain letters, digits, dot, underscore and hyphen only.",
  "violations": [
    { "propertyPath": "name", "title": "...", "code": "..." }
  ]
}
```

`404` for missing mailbox / message. `500` for auth failure on the webhook (exception not mapped to 401 in current code).

---

## 11. Source Map (for code-reading agents)

| Concern | File |
|---|---|
| Mailbox endpoints | [src/Controller/EmailBoxController.php](../src/Controller/EmailBoxController.php) |
| Webhook endpoint | [src/Controller/ApiReceiveEmailController.php](../src/Controller/ApiReceiveEmailController.php) |
| Webhook auth check | [src/Service/Validator/ReceivedEmail/CreateReceivedEmailAuthValidator.php](../src/Service/Validator/ReceivedEmail/CreateReceivedEmailAuthValidator.php) |
| Request DTOs | [src/DTO/Request/](../src/DTO/Request/) |
| Response DTOs | [src/DTO/Response/](../src/DTO/Response/) |
| HTML docs page | [src/Controller/DocsController.php](../src/Controller/DocsController.php) at `GET /docs` |
| Cloudflare Worker setup | [docs/CLOUDFLARE_EMAIL_WORKER_SETUP.md](CLOUDFLARE_EMAIL_WORKER_SETUP.md) |
