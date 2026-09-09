# Verifier BPHN Global Access

**Date:** 2026-08-13  
**Status:** Implemented  
**Resource:** `VerifierAccessResource`

## Goal

Allow verifier access rows to grant **global** access (all instansi) for a chosen jabatan by selecting **Verifikator BPHN**, without a separate lookup table.

## Approach

Make `verifier_accesses.entity_type` / `entity_id` nullable. When scope is BPHN-wide, both are null. When scope is regional, store the existing morph pair (department, province, or regency).

The form uses a **Ruang Regional** select (`Verifikator BPHN` vs `Instansi spesifik`) and shows the MorphToSelect instansi picker only for regional scope.

## Query rule

For verifier-access users, a row matches a client when:

- `c_role_id` matches, **and**
- `entity_id` is null (BPHN global) **or** (`entity_type` + `entity_id` match client agency)

Same pattern as global admin access in `ClientAccessService`.

## UI

- Ruang Regional: `Verifikator BPHN` | `Instansi spesifik`
- Instansi MorphToSelect visible and required only for `Instansi spesifik`
- Table shows **Verifikator BPHN** when `entity_id` is null

## Data model

- No new tables
- Migration makes `verifier_accesses.entity_type` / `entity_id` nullable
- If a prior `verifier_bphn_scopes` table existed, migration converts those rows to null region and drops the table

## Out of scope

- Changing system roles
- Admin access changes
