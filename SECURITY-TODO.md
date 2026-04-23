# Security TODO

## CRITICAL — Fix Immediately

- [ ] ~~**Remove `Model::unguard()`** from `app/Providers/AppServiceProvider.php:45`~~ — SKIPPED: Required by FilamentPHP for resource form handling. Mitigate by adding explicit `$fillable` to all models (see HIGH item).
- [ ] **Implement `ClientActivityPolicy`** — currently empty, allowing any authenticated user to create, view, edit, and delete client activities. Add proper `viewAny`, `view`, `create`, `update`, `delete` methods.
- [ ] **Add per-record authorization to verification actions** — `AcceptClientActivityAction`, `RejectClientActivityAction`, `AcceptClientIdentityAction`, `RejectClientIdentityAction`, and `VerifyPointSubmissionAction` check page access but not record ownership or verifier scope.
- [ ] **Add `->disk('s3')`** to 3 FileUpload fields in `app/Filament/Pages/Client/Point/ClientPointCreate.php` (lines ~316, 330, 344). Currently falls back to the local `public` disk, exposing sensitive PAK/SKP documents publicly.
- [ ] **Fix `canVerifying()` boolean logic** in `app/Filament/Pages/Verification/PointSubmissionVerificationWorkspace.php:55` — change `!auth()->user()->isSuperAdmin() || !auth()->user()->hasRole('verifier')` to `auth()->user()->isSuperAdmin() || auth()->user()->hasRole('verifier')`.

## HIGH — Fix Before Next Release

- [ ] **Complete `ActivityReportPolicy`** — only `viewAny()` is defined; all other operations default to allow. Add `view`, `create`, `update`, `delete` methods with proper role/permission checks.
- [ ] **Add data scoping to client sub-resources** — `ClientActivityResource`, `ClientGradeResource`, `ClientDossierResource`, `ClientEducationResource`, `ClientCompetenceResource`, `ClientPositionResource` all show ALL records to every user. Override `getEloquentQuery()` to scope by current client.
- [ ] **Add ownership verification to `ClientPointEdit`** — any client can edit another client's submission by visiting the URL directly. Check `auth()->user()->client->id === $pointSubmission->client_id` in `mount()`.
- [ ] **Define `$fillable` on all 29 models** that currently lack it. Without `unguard()` they will default to blocking all mass assignment, but with it they accept everything. Each model needs explicit column whitelisting.
- [ ] **Add `HasResourceShield` trait** or equivalent authorization overrides to all Filament Resources. Currently no resource enforces shield-based permissions.
- [ ] **Add `shouldRegisterNavigation()` and `canViewAny()`** to `AdminAccessResource` and `VerifierAccessResource` — these sensitive access-control resources are visible to all authenticated users.
- [ ] **Fix session secure cookie default** in `config/session.php:173` — change `env('SESSION_SECURE_COOKIE')` to `env('SESSION_SECURE_COOKIE', true)` so cookies default to HTTPS-only.

## MEDIUM — Fix Soon

- [ ] **Publish and configure CORS** — `fruitcake/php-cors` is installed but `config/cors.php` does not exist. Run `php artisan config:publish cors` and restrict `allowed_origins` to the application domain.
- [ ] **Create security headers middleware** — add `SetSecurityHeaders` middleware setting `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Content-Security-Policy`, `Referrer-Policy: no-referrer`. Register in `bootstrap/app.php`.
- [ ] **Fix `RolePolicy` template placeholders** — lines 66–106 contain literal `{{ ForceDelete }}`, `{{ Restore }}`, etc. as permission strings instead of actual permission names. These operations are always denied.
- [ ] **Fix `ClientCompetenceResource` file upload** — add `->maxSize(config('fungsional-pro.max_upload_file_size'))` and `->visibility(config('fungsional-pro.s3.visibility'))` to `competence_file` field at line 90.
- [ ] **Add `->directory()`** to 5 file uploads in `ClientResource.php` (`file_employee_card`, `sk_cpns_file`, `sk_pns_file`, `sk_latest_jf_file`, `sk_latest_grade_file`) — files currently land in S3 bucket root.
- [ ] **Remove `Log::debug(FilamentShield::getPages())`** from `app/Filament/Resources/Shield/RoleResource.php:271` — dumps entire permission structure to logs.
- [ ] **Fix `ClientProfilePage.authorizeAccess()`** at line 293 — `can(['create_client', 'update_client'])` requires BOTH permissions. Change to `canAny(['create_client', 'update_client'])` so users with either permission can access the page.
- [ ] **Create `.env.example`** file documenting all required environment variables for new developers.

## LOW — Address When Possible

- [ ] **Review `DB::unprepared()` in Deploy command** (`app/Console/Commands/Deploy.php:64`) — currently mitigated by file whitelist but inherently risky.
- [ ] **Move `Log::info()` to `Log::debug()`** in `ListClients.php:59` — logs user ID on every list view.
- [ ] **Enable session encryption** — set `SESSION_ENCRYPT=true` in production if session data contains sensitive information.
- [ ] **Add explicit `->directory()`** to all S3 file uploads for organizational hygiene even where visibility is already set.