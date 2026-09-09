# Pengajuan Ukom

**Date:** 2026-08-31  
**Status:** Approved for planning  
**Area:** Filament panel — applicant Pengajuan Ukom; admin Verifikasi Pengajuan Ukom; public Register  
**Related:** Client menu verification gate (`is_verified`); User Resource client sync (`isActiveClient()`); Akses Admin instansi scoping

## Goal

1. Let **existing JF clients** (AH and PH) and **non-JF PNS** submit **Pengajuan Ukom** with different persyaratan packs.
2. Keep **`c_role_id` as current jabatan only**. A person who is not yet AH or PH is **not** a Client and must not get a `clients` row or a fake CRole.
3. Two-step verify: **`admin-instansi`** (that instansi) forwards to **`admin`** (all instansi) for final decide.
4. Final accept does **not** create a Client. Conversion to Client is a later process.

## Context

- Today Register always creates `User` + `Client` and requires Jabatan (`c_role_id` 1 = Analis Hukum, 2 = Penyuluh Hukum). `Client::current()`, kegiatan, laporan, and matching assume that FK is real identity.
- Until Identitas is verified, a client-only user only gets Identitas under Profil Saya. Pengajuan Ukom is a **new** menu: visible to clients even before that, but **filling** waits on Identitas **and** verification.
- `User::isActiveClient()` = `client` role **and** a Client row. `canAccessPanel()` allows staff **or** active clients. Non-JF applicants need a new role and panel access **without** a Client.
- `AdminAccess`: `admin-instansi` is scoped to a region + `c_role_id`; `admin` is global for assigned jabatan. Dual `admin` + `admin-instansi` is treated as `admin`.
- Official NIP/nama for non-JF must **not** be new identity columns on `users`. Laravel still has `users.name` as a display label.

## Approach

**Chosen:** User-owned `ukom_applications` + role/table **`calon_jf` / `calon_jfs`**. Client is optional context (copy Identitas). Documents and instansi live on the application.

Rejected:

- Null `c_role_id` on Client for “belum JF” — Client means already AH or PH.
- Overloading Register Jabatan as *target* intent — mis-labels people missing from Master JF.
- NIP/nama on `users` as source of truth.
- Separate calon vs client pengajuan stacks, or polymorphic applicant (`Client` | `CalonJf`).
- Creating a Client when ukom is accepted.

## Architecture

```text
Register “Jabatan saat ini”
  ├── AH / PH     → User + role client + Client (c_role_id 1|2)
  └── Bukan keduanya → User + role calon_jf + calon_jfs (nip, nama)
                              no Client

Login (client | calon_jf)
  → Pengajuan Ukom (submit menu)
       client: menu always visible; form blocked until Identitas exists
               AND is_verified = Verified
               persistent notice until then
       calon_jf: form open (no Identitas)

ukom_applications.user_id
  target_c_role_id + instansi snapshot + documents + status

Kirim → pending_instansi
         admin-instansi (AdminAccess: instansi + target JF)
              Tolak → rejected | Teruskan → pending_admin
         admin (all instansi)
              Tolak → rejected | Terima → accepted
         accepted ≠ Client
```

## Data model

```text
users                 auth (email, password, name = display only)
  ├── client          only if already AH/PH
  ├── calonJf         only if register “Bukan keduanya”
  └── ukomApplications   always user_id
```

### `calon_jfs`

| Column | Rules |
|---|---|
| `user_id` | unique FK → `users` |
| `nip` | unique |
| `nama` | required |

Created only for “Bukan keduanya”. Never for AH/PH. Accepting ukom does not delete or convert this row.

**NIP uniqueness:** `calon_jfs.nip` unique; application validation: NIP must not exist on `clients.nip` (and client register must not collide with `calon_jfs.nip`).

### `ukom_applications`

Owned by `user_id` (never `client_id`).

- `target_c_role_id` — FK `c_roles` (1 AH or 2 PH)
- Instansi snapshot: `agency_type` / `agency_id` (same morph idea as Client)
- `status` — see Statuses
- `rejection_reason` — required on reject, null otherwise
- `instansi_reviewed_by` / `instansi_reviewed_at`
- `admin_reviewed_by` / `admin_reviewed_at`
- Identity snapshot at submit: at least `nip`, `nama` (from Client Identitas or `calon_jfs`) so review stays stable if profile data changes later

**Open statuses:** `draft`, `pending_instansi`, `pending_admin`. **At most one open row per `user_id`** (enforce in app + test; DB unique partial index if the driver allows).

### Documents

Child table `ukom_application_documents`: application, document type, file path.

Seeded `ukom_document_types`: pack (`calon_jf` | `client`), label, required flag, sort. Formasi labels that mention a jabatan use the application **target** name (AH or PH), not a hardcoded “Analis Hukum” only.

File types: `config('fungsional-pro.accepted_document_type')`.

### Packs

Pack is **applicant kind** (has Client vs `calon_jf`), not four “sudah/belum × target” matrices.

**Calon_jf (required unless noted)**

1. Surat usulan resmi dari Pejabat Penilai Kepegawaian (Kepala Biro SDM / Sekda / Kepala BKD) kepada Kepala BPHN
2. Salinan SK Pengangkatan PNS
3. Salinan SK Jabatan Terakhir
4. Salinan SK Pangkat Terakhir (atau SK CPNS jika belum pernah naik pangkat)
5. Salinan Ijazah pendidikan terakhir; extra BKN gelar file required only if they tick “peningkatan pendidikan”
6. SKP / hasil evaluasi kinerja 2 tahun terakhir
7. Surat keterangan pimpinan unit kerja (min. JPT Pratama / Eselon II): tidak hukuman disiplin, tidak tugas belajar, tidak CLTN
8. Surat keterangan sehat jasmani & rohani (faskes pemerintah, berlaku maks. 1 bulan)
9. Surat keterangan pengalaman kerja min. 2 tahun (pimpinan min. JPT Pratama)
10. Pendukung pengalaman (SK Tim Kerja / Surat Tugas / Surat Perintah)
11. Persetujuan kebutuhan formasi dari Kementerian PANRB
12. Hasil uji kompetensi manajerial & sosial kultural (min. 68% JPM)
13. Sertifikat pelatihan teknis/fungsional — **optional**

**Client (required unless noted)**

1. Surat usulan resmi dari Pejabat Pembina Kepegawaian kepada Kepala BPHN
2. Salinan SK Jabatan Terakhir
3. Salinan SK Pangkat Terakhir
4. SKP dan hasil evaluasi kinerja terhitung sejak pengangkatan/kenaikan pangkat terakhir
5. Konversi predikat kinerja ke angka kredit (same period)
6. Akumulasi angka kredit pejabat fungsional
7. PAK terakhir yang merekomendasikan kenaikan jenjang jabatan
8. Riwayat PAK sejak pengangkatan/kenaikan pangkat terakhir
9. Persetujuan/pencantuman gelar BKN — required only if they tick “klaim ijazah baru”
10. Persetujuan formasi JF **[target]** dari Kementerian PANRB
11. Hasil uji kompetensi manajerial & sosial kultural (min. 68% JPM)
12. Sertifikat pelatihan fungsional/teknis — **optional**

## Register + access

Public Register label **Jabatan saat ini** with options Analis Hukum, Penyuluh Hukum, **Bukan keduanya**. “Bukan keduanya” is a form sentinel only — **not** a `c_roles` row and **not** `c_role_id = 3`.

| Choice | Creates | Role |
|---|---|---|
| Analis Hukum | Client `c_role_id = 1` | `client` |
| Penyuluh Hukum | Client `c_role_id = 2` | `client` |
| Bukan keduanya | `calon_jfs` (nip, nama); **no** Client | `calon_jf` |

- Master JF NIP match that infers AH/PH still auto-fills and **locks** the select (cannot pick “Bukan keduanya” when master already says they are JF).
- **Bukan keduanya** fields: NIP, nama, email, password. **No instansi** at register.
- `users.name` is set from nama for Filament display; official calon_jf nama is `calon_jfs.nama`.

`SystemRole::CalonJf = 'calon_jf'`. Seed the Spatie role.

`User::canAccessPanel()`: existing staff/client rules **or** `hasSystemRole(CalonJf)` with a `calon_jfs` row (mirror `isActiveClient()`).

**Submit menu Pengajuan Ukom:** `client` (active client) **or** `calon_jf` (with `calon_jfs`). Staff without those roles do not get the submit menu.

**Profil Saya / Identitas:** still `isActiveClient()` only. Calon_jf never sees Identitas.

## Applicant UI

- Menu visible for client and calon_jf.
- **Client:** form disabled until `identity()` exists **and** `is_verified === Verified`. Persistent notice: *“Lengkapi identitas dan tunggu verifikasi untuk daftar ukom.”* Save/kirim rejected while blocked. Unverified and Rejected identity both block.
- **Calon_jf:** no that notice; form usable after login.
- One page: current/open pengajuan, or create when none open; previous rows as history.
- Header: NIP + nama read-only (Identitas vs `calon_jfs`). Instansi read-only from Client (copied to snapshot) **or** required select for calon_jf (same tingkat + instansi as Register). Target AH/PH required.
- **Draft** allows incomplete uploads. **Kirim** requires all required slots + instansi + target → `pending_instansi`.
- No edit while `pending_instansi` or `pending_admin`. After `accepted` or `rejected`, user may start a new pengajuan.

## Verifikasi Pengajuan Ukom

One Filament resource/menu. Drafts are not listed.

| Actor | Sees | Acts on | Actions |
|---|---|---|---|
| `admin-instansi` | Applications whose **instansi snapshot** matches `AdminAccess` entity **and** `target_c_role_id` matches that access `c_role_id` | `pending_instansi` only | **Teruskan** → `pending_admin`; **Tolak** → `rejected` (alasan required). After forward: view-only |
| `admin` | Applications that reached pembina (`pending_admin` or `admin_reviewed_at` set). Does **not** see `pending_instansi` or instansi-only rejects. See [2026-09-08-ukom-verification-tabs-design.md](./2026-09-08-ukom-verification-tabs-design.md). | `pending_admin` only | **Terima** → `accepted`; **Tolak** → `rejected` (alasan required). Cannot skip or perform the instansi step |
| Both `admin` and `admin-instansi` | Treat as `admin` | `pending_admin` | Same as `admin` |
| SuperAdmin | All | Same final actions as `admin` | Same as `admin` |

Accept does **not** create `clients`, assign `client`, or set `c_role_id`.

## Statuses

`draft` → `pending_instansi` → `pending_admin` → `accepted` | `rejected`

No revision/send-back status. Reject closes the row; applicant starts a new one. History is kept.

## Errors and notifications

- Second open pengajuan: reject create/kirim.
- Wrong-stage or out-of-scope admin action: no action / 403.
- Calon_jf kirim without instansi: validation error.
- Applicant **in-app Filament notification** when instansi forwards, when admin accepts, and when either side rejects (include alasan). Email is out of scope for v1.

## Testing

1. Register “Bukan keduanya” → User + `calon_jf` + `calon_jfs`, no Client. AH/PH still create Client with `c_role_id`. NIP unique across `clients` and `calon_jfs`.
2. Calon_jf panel access without Client; Identitas hidden; Pengajuan Ukom open.
3. Client: Pengajuan Ukom nav visible; form/save blocked until Identitas exists and verified; persistent notice copy as specified.
4. Pack switches client vs calon_jf; formasi label follows target; optional sertifikat; BKN gelar only when the matching tick is on.
5. At most one open pengajuan per user; after reject/accept a new one is allowed.
6. `admin-instansi` lists/acts only `pending_instansi` in scope; Teruskan / Tolak.
7. `admin` final-acts only `pending_admin`; accept does not create a Client.
8. Dual-role `admin` + `admin-instansi` behaves as `admin`.

## Non-goals

- Creating or syncing a Client when ukom is accepted (including copying `calon_jfs` → Client).
- Deleting `calon_jfs` if they later become a Client.
- User Resource create/edit for `calon_jf`.
- Changing Identitas verification itself, Kegiatan/Diklat/Pendidikan gates, or Akses Admin assignment UI.
- Email for ukom status.
- Separate persyaratan lists per AH vs PH beyond substituting the target name on formasi.
