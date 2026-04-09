# AAISP SMS Proxy

Translates AAISP plain-text SMS API responses into Groundwire-compatible XML,
stores inbound SMS from AAISP, and serves them to Groundwire's SMS fetcher.
AAISP credentials are kept server-side.

## Groundwire: SMS Sender

Settings > Account > Web Services > SMS Sender

| Field | Value |
|---|---|
| URL | `https://your-domain.example.com/index.php?token=YOUR_SMS_TOKEN&account=%account[username]%&da=%sms_to%&ud=%sms_body%` |
| Method | GET |
| Everything else | leave blank |

## Groundwire: SMS Fetcher

Settings > Account > Web Services > SMS Fetcher

| Field | Value |
|---|---|
| URL | `https://your-domain.example.com/fetch.php?token=YOUR_SMS_TOKEN&account=%account[username]%&last_known_sms_id=%last_known_sms_id%` |
| Method | GET |
| Everything else | leave blank |

Groundwire polls this endpoint automatically. Based on observed behaviour:
- **Active use**: polls every ~30–60 seconds
- **Idle**: backs off to ~3 minute intervals
- **Immediate poll**: opening the Groundwire messages screen triggers a fetch right away

> **Note:** `fetch.php` deletes messages from the database after returning them. Do not call it manually for diagnostics — use `status.php` instead (see below).

## AAISP Control Panel: Inbound SMS Target

Set the inbound SMS target for each number to:
```
https://your-domain.example.com/receive.php?token=YOUR_RECEIVE_TOKEN
```

## Adding a new AAISP number

1. Edit `.env` and add:
   ```
   AAISP_44XXXXXXXXXX_USERNAME=+44XXXXXXXXXX
   AAISP_44XXXXXXXXXX_PASSWORD=thepassword
   ```
   Note: the key is the number without the leading + (e.g. 447911000000 for +447911000000)

2. Recreate the container to load the new env:
   ```
   docker compose up -d --force-recreate
   ```

3. In Groundwire, configure the new SIP account with the same URLs above — no changes needed.

4. In the AAISP control panel, set the inbound SMS target for the new number to the receive URL above.

## Checking pending messages (diagnostics)

`status.php` shows messages currently queued in the database without deleting them:

```
https://your-domain.example.com/status.php?token=YOUR_SMS_TOKEN
```

Returns a JSON array of up to 20 pending messages. If a message appears here but not in Groundwire, wait for the next poll or open the Groundwire messages screen to trigger one immediately.

## .env structure

See `.env.example` for the full template.

## Data retention

Inbound messages are stored in SQLite only until Groundwire fetches them. Once delivered, they are deleted automatically. No SMS content is persisted long-term.

## File layout

```
/
├── Dockerfile
├── docker-compose.yml
├── .env               (not committed — copy from .env.example)
├── .env.example
├── config/
│   ├── index.php      outbound SMS (Groundwire → AAISP)
│   ├── receive.php    inbound webhook (AAISP → proxy)
│   ├── fetch.php      message fetcher (Groundwire polls this — deletes on read)
│   └── status.php     read-only diagnostic view of pending messages
└── data/              (not committed — created at runtime)
    └── messages.db    SQLite message store
```
