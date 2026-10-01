# Observation log (1 Oct – 1 Nov 2026)

These files record what changes in the POS project during October 2026, for a report on
**1 November 2026** with suggestions for automation.

## How it runs when nobody has Claude open

Claude Code sessions run in a temporary cloud container that is wiped after it sits idle,
so a process left running inside it would not last the month. Instead, scheduled
**Routines** on the claude.ai account (server-side, they fire whether Claude is open or not)
wake the session that set this up:

| Routine | Schedule | What it does |
|---|---|---|
| POS daily observer | Every day 20:52 IST, through 31 Oct | Fetches the repo, records new commits, branches, PRs, issues and CI runs since the last entry, adds a dated entry to `LOG.md` and pushes it |
| POS monthly report | Once, 1 Nov 2026 09:00 IST | Writes `REPORT-2026-11.md` from `LOG.md` plus the GitHub history, publishes it, and turns the daily observer off |

Each entry is committed to branch `claude/gifted-rubin-fpbyxs`, so the log survives even
though the container does not.

## What "observe" covers

- Covered: everything that reaches GitHub for `soulbowlonline-del/pos`, meaning commits on
  every branch, branches created or deleted, pull requests, issues, Actions runs and
  releases, plus a fresh read of the code for automation candidates.
- Not covered: the Windows desktops, Excel, Tally, the browser and Gmail. The existing
  "Daily activity observer" and "Work flow observer" Routines watch those. The 1 Nov report
  summarises what they found instead of repeating their work.
- Not covered: the live POS server (daspos.com / 192.168.100.16). This container has no
  access to it.
