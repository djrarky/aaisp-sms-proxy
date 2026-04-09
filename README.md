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

## .env structure

See `.env.example` for the full template.

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
│   └── fetch.php      message fetcher (Groundwire polls this)
└── data/              (not committed — created at runtime)
    └── messages.db    SQLite message store
```
