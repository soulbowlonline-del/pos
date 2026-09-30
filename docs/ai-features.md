# AI features (`/v2/ai`)

Added 30 Sep 2026, on the owner's instruction: *"build the AI Native features in the Web PHP ... without changing the DOT NET and Android code for now"*, with the earlier condition that they must not change any existing formula or calculation.

They live only in the port (`app2/`), under `/v2/ai`, and only admins (role 1) see them. Yii 1, the .NET till app, the Android app and every existing screen are unchanged.

## What there is

| Screen | What it does | Cost |
|---|---|---|
| **Insights** `/v2/ai/insights` | Twelve daily checks worked out by the database: running out soon (28-day sales vs stock), batches below zero, stock not selling for 60 days, GRNs unapproved for 5+ days, lines sold above MRP, possible duplicate bills, bill numbers used twice, discounts by cashier, credit notes used beyond their value, items without HSN, duplicate item names, one barcode on several items. Each row links to the existing screen where it is fixed. | Free - no AI |
| **Summarise for me** (on Insights) | Claude reads the check results and writes a short briefing in English, Hindi or Punjabi. | ~₹0.5-1.5 |
| **Ask DASPOS** `/v2/ai/ask` | Questions in plain words ("milk sales this week vs last week", "top 10 items this month", "stock of Verka paneer"), answered from the live data through ten read-only lookups. The lookups used are listed under each answer. | ~₹1-3 a question |
| **Read a vendor bill** `/v2/ai/bill` | Upload a photo or PDF of a supplier's bill. Claude reads the lines; each is matched to the item master (barcode, then the vendor's item code, then the name) and checked: MRP vs master, GST vs the item's slab, HSN, rate vs the last purchase, expiry, and whether the lines add up to the bill total. The result is a checklist and a CSV. **Nothing is saved** - the GRN is entered on the usual screen. | ~₹3-8 a bill |
| **Usage** `/v2/ai/usage` | Spend this month against the limit, by feature, by day, and the last 30 calls. | - |

The entry point is the magic-wand icon in the top bar (admins only), or `/v2/ai`.

## What it will not do

- **Change anything.** Every query is a `SELECT` written in `app2/components/ai/` with bound parameters, capped at 20 s on MySQL 8 (`MAX_EXECUTION_TIME`). The model picks a lookup and fills in dates or an item name; it never writes SQL. No stock, price, bill, GRN, MRS or item is created or edited by any AI screen. The MRS "AI qty" column and its formula are untouched.
- **Send customer data.** No lookup returns a customer's name or number; customer questions are answered in counts. Anything that looks like a phone number or an e-mail is masked before it is sent or logged (`AiPrivacy`). Staff names (cashiers) and vendor names are sent where a question needs them.
- **Spend without limit.** Every call is logged in `tbl_ai_log` with its cost. Before each call the month's total is compared with `POS_AI_MONTHLY_BUDGET_USD` (default $50); at the limit the AI features pause until the 1st and the rest of the ERP carries on. The code refuses to call Claude at all while `tbl_ai_log` is missing. A hard limit can also be set in the Anthropic console.
- **Work for everyone.** Only roles in `POS_AI_ROLES` (default `1`, admin) get in; others get a 403. POSTs are CSRF-checked (unlike the rest of the port), because they spend money.

## Setting it up on a server

1. `git pull` (the code).
2. Install the two new libraries - the official Anthropic PHP SDK and Guzzle - from `composer.lock`. The lock adds 11 packages and changes none of the existing ones:

   ```bash
   docker exec -w /var/www/html -e COMPOSER_ALLOW_SUPERUSER=1 pos-php-83 composer install --no-dev --no-interaction --no-plugins --no-scripts --dry-run
   # expect only "Installing" lines for anthropic-ai/sdk, guzzlehttp/*, psr/http-*, php-http/discovery,
   # ralouphie/getallheaders, standard-webhooks/standard-webhooks, symfony/deprecation-contracts, symfony/polyfill-php80
   docker exec -w /var/www/html -e COMPOSER_ALLOW_SUPERUSER=1 pos-php-83 composer install --no-dev --no-interaction --no-plugins --no-scripts
   ```

   If this step is skipped, the AI screens say the library is missing; nothing else is affected.
3. `db_changes/2026-09-30-ai-log.sql` (creates `tbl_ai_log`; pure addition).
4. Put the key in `.env`: `ANTHROPIC_API_KEY=sk-ant-...`. Optional: `POS_AI_MODEL`, `POS_AI_MONTHLY_BUDGET_USD`, `POS_AI_ROLES`. `.env` is read on every request; no restart is needed.

Without step 4, Insights works (it needs no key) and the Claude screens explain that no key is set.

## How it calls Claude

- The official Anthropic PHP SDK (`anthropic-ai/sdk`), beta messages endpoint, model `claude-sonnet-5-5` unless `POS_AI_MODEL` says otherwise. It was `claude-opus-5-5` until 30 Sep 2026; the owner switched to Sonnet 5.5, which costs half as much per token ($2/$10 per million input/output against $4/$20; cached input is $0.20 on both). The first real questions on Opus 5.5 cost about ₹3.5 each; the same token counts on Sonnet 5.5 come to about ₹1.8. `POS_AI_MODEL=claude-opus-5-5` in `.env` switches back.
- Server-side refusal fallback on (`fallbacks: "default"`, beta `server-side-fallback-2026-07-01`), for the models that take it (Opus 5.5, Opus 5, Sonnet 5.5, Fable 5.1): if the model declines a request on policy grounds, the API retries it on its default fallback model. Every attempt - the declined one and the one that answered - is read from `usage.iterations` and priced at its own model's list price; an unknown model is priced at the dearest one, so the budget errs high.
- Ask DASPOS: ten strict tools (`AskTools`), effort `low`, at most six rounds of lookups, 75 s per call and no new round after 100 s. Bill reading: image or PDF block plus a JSON schema (structured output), effort `medium`, 110 s. Summary: effort `low`, 110 s. The system prompts carry a cache marker; they may be shorter than the model's minimum cacheable length, in which case caching simply does not happen.
- One retry on a transport failure or 429/5xx. Errors reach the screen as a sentence and are logged as `status = error` with no cost; a call that timed out is logged as `timeout` at a typical call's cost ($0.05 ask, $0.15 bill, $0.03 summary), since Anthropic may have done the work.
- The PHP session is released before each Claude call, so the user's other tabs (Yii 1 and the port share the session) do not wait for the answer.
- The summary answers POST only (CSRF-checked); a GET is refused, so no link can start a paid call. Text read from a bill is written to the CSV with a leading `'` where it would otherwise start a spreadsheet formula.
- `POS_AI_BASE_URL` exists only for tests against a stand-in server; the SDK's own `ANTHROPIC_BASE_URL` is deliberately ignored so a value in the host's environment cannot redirect the store's key.

## Files

- `app2/components/ai/` - `AiConfig` (settings), `AiClient` (the one place Claude is called; budget, logging, errors), `AiLog`, `AiData` (read-only query helper), `AiPrivacy`, `Insights` (the twelve checks), `AskTools` + `AskDaspos`, `BillReader`, `InsightSummary`, `AiMarkdown` (answers to escaped HTML).
- `app2/controllers/AiController.php`, `app2/views/ai/`, `v2/css/ai.css`.
- Not in `Ui::PORTED` on purpose: the parity sweeps read that list and would look for an `ai` controller on Yii 1. Yii 2's default route parsing serves `/v2/ai/<action>`.

## How it was tested (30 Sep 2026, locally)

No real API key was available, so Claude was replaced by a stand-in server that records every request and answers from scripts (tool calls, final answers, a bill extraction, a 500, a 429, a refusal).

- Wire format checked from the recorded requests: strict tool schemas with `additionalProperties: false`, `cache_control` on the system prompt, the fallback beta header, `tool_result` round trips with the thinking blocks passed back unchanged, image and PDF blocks, the JSON-schema output format.
- Every check against seeded data with one planted case each: each found exactly its case.
- Ask: three questions through three different tools; 500 → "busy" message; 429 → "too many requests"; refusal → "declined"; a fallback-served answer priced per attempt (declined Opus 5.5 attempt + Opus 4.8 answer = $0.01890, as worked by hand); a phone number and an e-mail in a question masked before sending and in the log; no CSRF token → 400; GET on the summary → 405.
- Bill: PNG upload → 3 of 3 lines matched (barcode, name 86%, name 100%), vendor found by GSTIN, flags for expiry, GST, MRP and rate; a non-image rejected; CSV download.
- Budget at $0.05 → refused with the budget message and logged as `budget`; no key → "no key" message on every Claude screen; table missing → refused; costs in `tbl_ai_log` match the price table to the fifth decimal.
- Access: a role-3 user gets 403 on every AI URL; a guest is sent to the login page.
- In a browser (Chromium): the four screens and the flows above with no script errors, in both the modern and the classic look.
- An independent review of this code found, among smaller things, that a GET could start a paid summary and that fallback attempts were under-priced; both are fixed and covered above. 56 scripted checks pass.

**Not tested: a real call to Claude.** The first real question, bill and summary after the key is added are the real test - check `/v2/ai/usage` after them.

## Not done (next steps, each needs the owner's go-ahead)

- Filling the GRN screen from a read bill (it touches the GRN screen, which Yii 1 shares; today the checklist is read alongside it).
- The evening WhatsApp digest of Insights (outbound messages are stubbed on the test server, and the database has real customer numbers).
- Reorder drafts written into MRS/PO, the item-master clean-up with suggested HSN/GST, and the .NET/Android features.
