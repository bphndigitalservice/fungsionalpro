# Ukom Verification Tabs & Pembina Visibility

**Date:** 2026-09-08  
**Area:** Filament — Verifikasi Pengajuan Ukom (`UkomApplicationResource` list)  
**Approach:** Keep Resource list; add All / New / Processed tabs; tighten admin pembina list/view scope

## Problem

Verifikasi Pengajuan Ukom shows every in-scope application in one flat table. Verifiers need the same All / New / Processed split used on Verifikasi Pelaporan Kegiatan. Separately, admin (instansi pembina) must not see pengajuan that have not been forwarded to them yet.

## Decision

Stay on `UkomApplicationResource` / `ListUkomApplications` (no new Verification Workspace page). Add Filament list tabs and change `UkomApplicationAccess::scopedQuery` so pembina visibility matches “already forwarded (+ finished history).”

## Visibility (`scopedQuery`)

Drafts remain excluded for everyone (`status != draft`).

| Actor | Sees |
|---|---|
| `admin-instansi` only | Unchanged: non-draft rows whose instansi snapshot + `target_c_role_id` match `AdminAccess` (includes `pending_instansi`, and later statuses still in that agency/jabatan scope) |
| `admin` (pembina) | Only pengajuan that reached pembina: `status = pending_admin` **or** `admin_reviewed_at IS NOT NULL` (accepted / rejected by pembina). Hides `pending_instansi` and hides reject-at-instansi rows (`rejected` with no `admin_reviewed_at`) |
| Both `admin` and `admin-instansi` | Same as `admin` (existing treat-as-admin rule) |
| SuperAdmin | All non-draft statuses, including `pending_instansi` and instansi-only rejects |

List and view both go through this query, so pembina cannot open an undelivered or instansi-only-rejected record by URL either.

**Why not `status IN (pending_admin, accepted, rejected)` alone:** Instansi can reject before forward; that row is also `rejected` but must stay hidden from pembina. Existing columns `admin_reviewed_at` / `admin_reviewed_by` already mark pembina decisions; `pending_admin` marks the waiting queue after Teruskan.

**Clarification vs prior ukom design:** The original “admin sees all instansi” wording for the list is narrowed: pembina still covers all agencies for rows that reached them, but undelivered and instansi-only rejects are hidden. Action rules are unchanged (`admin` still acts only on `pending_admin`; cannot skip the instansi step).

## Tabs

Implemented on `ListUkomApplications`. Labels and default match Verifikasi Kegiatan: **All**, **New**, **Processed**; default active tab = **New**. Badge on **New** only (count of rows waiting on the current user within their scoped query). Columns and row actions (View, Teruskan, Terima, Tolak) stay as today; tabs only filter.

| Actor | New | Processed | All |
|---|---|---|---|
| `admin-instansi` only | `pending_instansi` | `pending_admin`, `accepted`, `rejected` | Full scoped set |
| `admin` (pembina) / dual-role-as-admin | `pending_admin` | `admin_reviewed_at IS NOT NULL` (accepted / pembina-rejected) | Full pembina-scoped set |
| SuperAdmin | `pending_instansi` **or** `pending_admin` | `accepted` **or** `rejected` | All non-draft |

After Teruskan / Terima / Tolak, the row leaves **New** on refresh (same UX as kegiatan after verify).

## Implementation touch points

1. `App\Services\UkomApplicationAccess::scopedQuery` — for non–instansi-only `admin` (and dual-role-as-admin), restrict with `status = pending_admin OR admin_reviewed_at IS NOT NULL`. SuperAdmin branch unchanged (all non-draft). Instansi-only branch unchanged.
2. `ListUkomApplications` — `getTabs()`, `getDefaultActiveTab(): 'new'`, New badge from filtered scoped count.
3. Spec cross-link: this document amends list visibility for Verifikasi Pengajuan Ukom in `2026-08-31-pengajuan-ukom-design.md` (pembina no longer lists `pending_instansi`).
4. No changes to status machine, notifications, accept→Client behavior, or document infolist.

## Testing

1. Pembina `scopedQuery` / list: cannot see `pending_instansi` or instansi-only `rejected`; can see `pending_admin` and rows with `admin_reviewed_at`.
2. SuperAdmin list: can see `pending_instansi` and instansi-only rejects.
3. Admin-instansi: New = `pending_instansi` only; after forward, row appears under Processed (still in scope).
4. Pembina: New = `pending_admin`; Processed = `admin_reviewed_at` set.
5. Dual-role user follows pembina visibility and tabs.
6. Update existing tests that assert `admin` can see/open a `pending_instansi` table/view row (use `pending_admin` for pembina, or SuperAdmin for undelivered visibility).

## Non-goals

- Rebuilding as a custom Verification Workspace page
- Changing Teruskan / Terima / Tolak rules or creating Client on accept
- Email notifications
- UI redesign beyond tabs + query filters
- Changing applicant Pengajuan / Riwayat pages
