# Pengajuan Ukom Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans. User asked to execute in this session and **not** to `git add` / `git commit`. Skip every Commit step.

**Goal:** Ship Pengajuan Ukom for `client` and `calon_jf`, with instansi-then-admin verification, without creating a Client on accept.

**Architecture:** User-owned `ukom_applications`; non-JF identity on `calon_jfs`; persyaratan from seeded `ukom_document_types`; uploads on the existing `s3` disk (MinIO locally). `c_role_id` stays current JF only.

**Tech Stack:** Laravel 12, Filament v3, Livewire 3, Spatie Permission, PHPUnit 11, `RefreshDatabase`, SQLite in-memory tests

## Global Constraints

- Spec: `docs/superpowers/specs/2026-08-31-pengajuan-ukom-design.md`
- Do **not** create Client for “Bukan keduanya”; do **not** set `c_role_id` on accept
- Do **not** add `users.nip`; official calon_jf nama/nip live on `calon_jfs`
- “Bukan keduanya” is a form sentinel, not a `c_roles` row
- Client may submit only when Identitas exists and `is_verified === Verified`; persistent notice until then
- One open pengajuan (`draft` | `pending_instansi` | `pending_admin`) per user
- Uploads: `->disk('s3')` like other Filament files
- No git add / commit in this execution
- PHPUnit (not Pest) for Filament tests, follow `tests/Feature/Filament/ClientMenuVerificationGateTest.php`

---

## File structure

| File | Responsibility |
| --- | --- |
| `app/Enums/SystemRole.php` | Add `CalonJf = 'calon_jf'` |
| `app/Enums/UkomApplicationStatus.php` | Status enum |
| `app/Enums/UkomDocumentPack.php` | `calon_jf` \| `client` |
| `database/migrations/*_create_calon_jfs_table.php` | calon identity |
| `database/migrations/*_create_ukom_document_types_table.php` | persyaratan catalog |
| `database/migrations/*_create_ukom_applications_table.php` | pengajuan |
| `database/migrations/*_create_ukom_application_documents_table.php` | file paths |
| `app/Models/CalonJf.php` | nip, nama, user |
| `app/Models/UkomDocumentType.php` | pack, slug, label, required |
| `app/Models/UkomApplication.php` | application + scopes |
| `app/Models/UkomApplicationDocument.php` | type + s3 path |
| `app/Models/User.php` | `calonJf()`, `ukomApplications()`, `isActiveCalonJf()`, panel access |
| `app/Rules/UniqueNip.php` | unique across `clients.nip` and `calon_jfs.nip` |
| `app/Services/UkomApplicationAccess.php` | submitter + admin-instansi/admin scope |
| `app/Notifications/UkomApplicationStatusNotification.php` | in-app Filament DB notification |
| `app/Filament/Pages/Authx/Register.php` | Jabatan saat ini + calon_jf path |
| `app/Filament/Pages/Ukom/PengajuanUkomPage.php` | applicant form |
| `app/Filament/Resources/UkomApplicationResource.php` | Verifikasi Pengajuan Ukom |
| `database/seeders/RoleSeeder.php` | seed `calon_jf` |
| `database/seeders/UkomDocumentTypeSeeder.php` | both packs |
| `database/seeders/RolePermissionSeeder.php` | page/resource permissions |
| `lang/id/labels.php` / `lang/en/labels.php` | nav + notice copy |

### Task 1: Schema + access helpers

- [ ] Models, migrations, enums, factories, `User` relations, `UniqueNip`, `UkomApplicationAccess`
- [ ] Tests: calon_jf persist; NIP collision; `isActiveCalonJf`; panel access; open-application helper
- Skip commit

### Task 2: Register

- [ ] “Jabatan saat ini” + Bukan keduanya → User + calon_jf + calon_jfs, no Client
- [ ] AH/PH still create Client; Master JF lock unchanged
- [ ] Tests for both paths and NIP unique vs both tables
- Skip commit

### Task 3: Applicant Pengajuan Ukom

- [ ] Page visible to client and calon_jf
- [ ] Client blocked + persistent notice until verified
- [ ] Calon_jf form open; instansi required on kirim
- [ ] Document packs; s3 disk; draft vs kirim; one open
- Skip commit

### Task 4: Verifikasi

- [ ] Resource in group Verifikasi
- [ ] admin-instansi: pending_instansi, instansi + target JF via AdminAccess
- [ ] admin: pending_admin, all instansi; accept does not create Client
- [ ] Dual-role treated as admin
- Skip commit

### Task 5: Notifications + seeders + lang

- [ ] Notify on forward / accept / reject (alasan)
- [ ] Seed document types and `calon_jf` role + permissions
- Skip commit
