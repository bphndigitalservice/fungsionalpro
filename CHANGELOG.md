# [0.3.0](https://github.com/bphndigitalservice/fungsionalpro/compare/v0.2.4...v0.3.0) (2026-07-28)


### Bug Fixes

* **master-jf:** clear table sort before stats groupBy ([70aa0a1](https://github.com/bphndigitalservice/fungsionalpro/commit/70aa0a1af511726bf7f7ee8e49cb942a9598a4e2))
* **test:** set APP_KEY in phpunit for Filament feature tests ([02f7579](https://github.com/bphndigitalservice/fungsionalpro/commit/02f75795c2a9c6afe9105722e5951ab311aaae12))


### Features

* **deployment:** update docker compose and environment ([728a251](https://github.com/bphndigitalservice/fungsionalpro/commit/728a251cde678bee51fd4188f4b4b110530fdfe4))
* **master-jf:** add filter-aware total stats widget and collapse toggle ([35a03d3](https://github.com/bphndigitalservice/fungsionalpro/commit/35a03d3746b7ecdb3b0264f79ef1d990de6e8fce))
* **master-jf:** add list filters and status_kepegawaian column ([d649ece](https://github.com/bphndigitalservice/fungsionalpro/commit/d649ece82434a9a74666e8c5c4329cab2e8abe54))
* **master-jf:** add status_kepegawaian fillable, options helpers, factory ([5e3b5af](https://github.com/bphndigitalservice/fungsionalpro/commit/5e3b5af1947eafc200fd727f7eb7a1182b3d9421))
* **master-jf:** add status, kepegawaian, and gol_ruang stats widgets ([f40d32b](https://github.com/bphndigitalservice/fungsionalpro/commit/f40d32b90b3a1b55ae679c28de8dc2f23d320052))

## [0.2.4](https://github.com/bphndigitalservice/fungsionalpro/compare/v0.2.3...v0.2.4) (2026-07-28)


### Bug Fixes

* **perf:** enhance docker configuration and optimize php settings for performance ([bb01301](https://github.com/bphndigitalservice/fungsionalpro/commit/bb0130198adfa8f72416cdae27d2b01d5aef9f21))

## [0.2.3](https://github.com/bphndigitalservice/fungsionalpro/compare/v0.2.2...v0.2.3) (2026-07-20)


### Bug Fixes

* **Dockerfile:** consolidate ARG declarations for Bun, Supercronic, and Golang versions ([9152acc](https://github.com/bphndigitalservice/fungsionalpro/commit/9152accf71d09cd4a40b1c5d1cef06549812de52))

## [0.2.2](https://github.com/bphndigitalservice/fungsionalpro/compare/v0.2.1...v0.2.2) (2026-07-20)


### Bug Fixes

* **Dockerfile:** add GOLANG_VERSION argument for consistency in build process ([0a48b54](https://github.com/bphndigitalservice/fungsionalpro/commit/0a48b547e0f6af3fb2a514dd9df0e6da56883854))

## [0.2.1](https://github.com/bphndigitalservice/fungsionalpro/compare/v0.2.0...v0.2.1) (2026-07-20)


### Bug Fixes

* **ci:** clear Trivy HIGH findings blocking image publish ([c856945](https://github.com/bphndigitalservice/fungsionalpro/commit/c85694560987713f9eae8c5072c2c8ff7a5dd7b7))

# [0.2.0](https://github.com/bphndigitalservice/fungsionalpro/compare/v0.1.0...v0.2.0) (2026-07-20)


### Bug Fixes

* add /sys/fs/cgroup to open_basedir for Swoole CPU detection ([6fdc333](https://github.com/bphndigitalservice/fungsionalpro/commit/6fdc333f4548565e6f30ab42e2132e95681c3078))
* add custom login error messages for email and password validation ([837cc8e](https://github.com/bphndigitalservice/fungsionalpro/commit/837cc8e5481fe5ba680347ec30f6ecde7c2dda58))
* add Model $record parameter to canEdit/canDelete in AdminAccessResource and VerifierAccessResource ([4f38c86](https://github.com/bphndigitalservice/fungsionalpro/commit/4f38c8628ee633a405c1e5fa6e8c969e2e2ec6ff))
* add Redis wait loop to start-container and update deployment compose ([cf9af51](https://github.com/bphndigitalservice/fungsionalpro/commit/cf9af5182621b3077dd8a8fae3cc0f437bb9e18a))
* add tmpfs mounts for Laravel writable paths under read_only ([7f5b9bd](https://github.com/bphndigitalservice/fungsionalpro/commit/7f5b9bdbf62c7285787c3ebe3713fc9aeff1f44d))
* align composer platform with CI and update Docker build ([08ce788](https://github.com/bphndigitalservice/fungsionalpro/commit/08ce7889cde4db49277643d26e59e076f4849f1c))
* built error on dockerfile and composer.json ([1d2763c](https://github.com/bphndigitalservice/fungsionalpro/commit/1d2763c22826e88d3d1a0dfd45309e2e9108fdb6))
* cache ([6344895](https://github.com/bphndigitalservice/fungsionalpro/commit/63448954a7f5313a6f2cac0c986252d9a38f6906))
* **ci:** resolve Trivy image tag mismatch in GHCR scan ([e3e9d96](https://github.com/bphndigitalservice/fungsionalpro/commit/e3e9d96171d2f7a3deda3c0346f8a335af31edef))
* **ci:** separate Trivy SARIF generation from threshold check ([2cf0c74](https://github.com/bphndigitalservice/fungsionalpro/commit/2cf0c74d90423eaa923a4a3119120419cf6395db))
* **ci:** trigger Docker build after Release via workflow_run ([6ef7acb](https://github.com/bphndigitalservice/fungsionalpro/commit/6ef7acb4e313662f72a8ae4b26d3de3331ab8822))
* **ci:** update codeql-action to v4 and add actions:read permission ([c9de91d](https://github.com/bphndigitalservice/fungsionalpro/commit/c9de91dc0f0634a08a80b765c0a533d0b4ca6b7f))
* Docker build and CI/CD errors ([e3ba2de](https://github.com/bphndigitalservice/fungsionalpro/commit/e3ba2dee9cdb17e7708dbe9ea3bbcdb3dada26b6))
* Docker build and CI/CD errors ([be3d82a](https://github.com/bphndigitalservice/fungsionalpro/commit/be3d82a091b58f16603b5c1870d9a47704cf7f0d))
* **docker:** add libxpm and firebird runtime libs for gd and swoole extensions ([d535955](https://github.com/bphndigitalservice/fungsionalpro/commit/d5359555ad40429b7c858c798dd5a75c365c1e6b))
* **docker:** add libxpm/libx11/liburing runtime packages, fix supervisord socket path ([ca51023](https://github.com/bphndigitalservice/fungsionalpro/commit/ca5102348c548385cba2d9af3842e640fb678622))
* **docker:** copy libfbclient and libXpm from builder instead of invalid apk packages ([c8b8e98](https://github.com/bphndigitalservice/fungsionalpro/commit/c8b8e98ad2794b8f375085f683c0c0b2c039c04c))
* Dockerfile security hardening and missing runtime dependencies ([c22a4fe](https://github.com/bphndigitalservice/fungsionalpro/commit/c22a4fe635cec29e3544d59a0a432a4fc180aac4))
* error $data ([72f7fa2](https://github.com/bphndigitalservice/fungsionalpro/commit/72f7fa2e0ca5feb4507424aa15884d2942d3b039))
* exception ([35c2f73](https://github.com/bphndigitalservice/fungsionalpro/commit/35c2f732681ef9eaef24a23bdc97878b7c1389c3))
* harden Dockerfile and deployment config for production ([3bdc814](https://github.com/bphndigitalservice/fungsionalpro/commit/3bdc8143f193a8c2ee0423a7fffea439c434b4db))
* labels, ([05f0401](https://github.com/bphndigitalservice/fungsionalpro/commit/05f04019f23d95daecf617f70371c0c5f3222426))
* mkdir storage and bootstrap/cache before chmod in Dockerfile ([d3c8a2d](https://github.com/bphndigitalservice/fungsionalpro/commit/d3c8a2d24a9caff397fc4e3224a298d2113f5824))
* move open_basedir out of php.ini into production-only ini ([452af68](https://github.com/bphndigitalservice/fungsionalpro/commit/452af6864354e3e156e3edb21e2cf006e0efc528))
* multiple accept ([16d5cf4](https://github.com/bphndigitalservice/fungsionalpro/commit/16d5cf4c11025970c8bcb40124c353cbdc23fa39))
* remove --frozen-lockfile from bun install for CI compatibility ([99befc9](https://github.com/bphndigitalservice/fungsionalpro/commit/99befc96b8a727f7c2dc612d9d427b0ee8ad5059))
* remove allow_url_fopen=0 — breaks AWS S3 SDK ([ef5ebb6](https://github.com/bphndigitalservice/fungsionalpro/commit/ef5ebb67268cda64852b122204e5c19e10e66ad0))
* remove proc_open/putenv from disabled functions, update supercronic to v0.2.45 ([5ec8668](https://github.com/bphndigitalservice/fungsionalpro/commit/5ec86681ba4d769b3dd5b00acd4cae4fd9e535ad))
* remove set -e from healthcheck, use explicit exit codes ([6a33bb1](https://github.com/bphndigitalservice/fungsionalpro/commit/6a33bb18b0e58f26c35dd9db192a9c0f04feaee2))
* remove the audit flag on dockerfile ([e8ea9ec](https://github.com/bphndigitalservice/fungsionalpro/commit/e8ea9ec5582675d0d954584398ea011ae03c0ae1))
* resolve 403 forbidden error on email verification ([795f520](https://github.com/bphndigitalservice/fungsionalpro/commit/795f52001488bec1a53614e30ff7083c44379b9a))
* resolve 403 forbidden on registration and restore email trigger ([0d76a34](https://github.com/bphndigitalservice/fungsionalpro/commit/0d76a3440da2558a44cf2a9bc7702e3693c8e46e))
* resolve composer security vulnerabilities ([cae0d92](https://github.com/bphndigitalservice/fungsionalpro/commit/cae0d9227a93a2cdcf132f20062fdfd1f9a1d3f5))
* reverse proxy conf ([a23c3d0](https://github.com/bphndigitalservice/fungsionalpro/commit/a23c3d055cf5f52da178d058e84e501b8001df6b))
* rollback resolve 403 forbidden error on email verification ([75a1f1e](https://github.com/bphndigitalservice/fungsionalpro/commit/75a1f1e4dfafd958c5d7b58cd3dad42ec43b2766))
* security hardening — policies, file uploads, auth, headers ([9e379ad](https://github.com/bphndigitalservice/fungsionalpro/commit/9e379ada9834ab1f76687091759986271d725959))
* shield roles mismatch ([f337352](https://github.com/bphndigitalservice/fungsionalpro/commit/f33735243c54fa63cab24adaf50e508fc76ffa71))
* status ([ed072ca](https://github.com/bphndigitalservice/fungsionalpro/commit/ed072cae469bad7721e6733a5b7b96ef10e81df2))
* status ([fdb0d18](https://github.com/bphndigitalservice/fungsionalpro/commit/fdb0d18a528a94ab68c5dda077f78661d703fa21))
* status ([750c547](https://github.com/bphndigitalservice/fungsionalpro/commit/750c5472d7ab093e78dc082379282e59aa4d0dd0))
* trust all ([4d22cc2](https://github.com/bphndigitalservice/fungsionalpro/commit/4d22cc2dc25b5da849e8acb92403d2cc8f02f06e))
* trusted proxy config ([390dd84](https://github.com/bphndigitalservice/fungsionalpro/commit/390dd84e503ae54cc7bd5ac04ae5b6390ba1e1c3))
* trusted proxy config causing 403 on signed URL verification (email verification) ([611fe32](https://github.com/bphndigitalservice/fungsionalpro/commit/611fe32494ccb005d805e225764229ec64d80d80))
* update client basic information form to handle echelon_id and echelon_x_text correctly based on selected client cluster ([2b41b1f](https://github.com/bphndigitalservice/fungsionalpro/commit/2b41b1fc4e2f5c556ba527df5dbae2a6e1a1e5f0))
* update client basic information form to handle echelon_id and echelon_x_text correctly based on selected client cluster ([214da64](https://github.com/bphndigitalservice/fungsionalpro/commit/214da64fdcb6e99180e44f8ce71014106960c5c2))
* update client identity form to handle echelon 1 selection for each agency type ([289c3d9](https://github.com/bphndigitalservice/fungsionalpro/commit/289c3d92d5ffd120136f2822557ba0222ee2ac2f))
* update install-php-extensions to v2.10.20 (was v2.5.3, too old for PHP 8.4) ([bde5568](https://github.com/bphndigitalservice/fungsionalpro/commit/bde55685fac762144e6e66c0c5af526a3adad9f5))
* upload file ([487337d](https://github.com/bphndigitalservice/fungsionalpro/commit/487337d67cf8806462c7c48663e3c7de4e5a79d5))
* use bzip2 instead of bzip2-libs (Alpine package name) ([3e65f2d](https://github.com/bphndigitalservice/fungsionalpro/commit/3e65f2d4fc8e80a117793af6c15f77363d06d000))
* verified_at when reject ([85dc9c6](https://github.com/bphndigitalservice/fungsionalpro/commit/85dc9c6f283610ca482ce93b442c7fd23af0117b))
* verifier note null ([0354695](https://github.com/bphndigitalservice/fungsionalpro/commit/03546950a80410ce88f322cbd9f22bf2d09fe4c7))
* verifier notes ([9b2c48b](https://github.com/bphndigitalservice/fungsionalpro/commit/9b2c48b58816c75880c232686988b7e0ecdad88f))


### Features

* add AdminInstansi to ActivityReportResource navigation and access ([4c8cbfa](https://github.com/bphndigitalservice/fungsionalpro/commit/4c8cbfa7d7ad932280574892ae7279798156fb07))
* add AdminInstansi to Dashboard admin widget visibility ([4e52c87](https://github.com/bphndigitalservice/fungsionalpro/commit/4e52c8787243eae2eddc058d265189c3fa87b83c))
* add AdminInstansi to Policy access groups ([18c61c4](https://github.com/bphndigitalservice/fungsionalpro/commit/18c61c4f66e8e9960362fe8f9b40e905c3378309))
* add AdminInstansi to SystemRole enum and RoleSeeder ([906528c](https://github.com/bphndigitalservice/fungsionalpro/commit/906528c3c6025d072af556953024f79b63eb48c5))
* add AdminInstansi to widget data scoping and client access ([6d4b22f](https://github.com/bphndigitalservice/fungsionalpro/commit/6d4b22f4255659d19b63a0d924a9c32e03bc1cbb))
* add AK submission verification notifications for client and fix minor problem on AK history table and AK verification list ([535869a](https://github.com/bphndigitalservice/fungsionalpro/commit/535869a88247ca2a3d9e82afb7931336764d250f))
* add APP_KEY guard to start-container ([0fe3d09](https://github.com/bphndigitalservice/fungsionalpro/commit/0fe3d090b1d34741f5f13d9c89a3befdc7dd4b10))
* add Docker deployment files and CORS configuration ([6094a03](https://github.com/bphndigitalservice/fungsionalpro/commit/6094a03404729200b9761fc4d0c0ab40f2569d15))
* add excel import and edit form for JF client master ([768cd16](https://github.com/bphndigitalservice/fungsionalpro/commit/768cd16767d9aca4afc86277b5ea058c4c1f7fc6))
* add fields for client education, client competence, then update infolists layout, and fix competence view logic ([b1d678a](https://github.com/bphndigitalservice/fungsionalpro/commit/b1d678af5b2f491638e7497489944010340b4d0d))
* add hasSystemRole/hasAnySystemRole helpers and migrate User model call sites ([5e0bae0](https://github.com/bphndigitalservice/fungsionalpro/commit/5e0bae0be1f5599593dd07d30b46979552d79c40))
* add identity verification notifications for client ([af5474e](https://github.com/bphndigitalservice/fungsionalpro/commit/af5474e6e935a76126172a3166806363694e1258))
* add polymorphic columns and resource logic for admin access (departement, province, and regional level) ([f025809](https://github.com/bphndigitalservice/fungsionalpro/commit/f0258094fd653f4c8293454a68147b96d9519eca))
* add Riwayat Kegiatan feature with submit form and history for Jabatan Fungsional ([886e1ac](https://github.com/bphndigitalservice/fungsionalpro/commit/886e1ac01f1c33fa7eb6d21a40923ae38195e52e))
* add Riwayat Kegiatan submission verification and add feature admin access to all JF Riwayat Kegiatan ([cf90dc9](https://github.com/bphndigitalservice/fungsionalpro/commit/cf90dc97f076271189c636eac2800fa97a6da07e))
* add SystemRole enum for type-safe role references ([38a414e](https://github.com/bphndigitalservice/fungsionalpro/commit/38a414effb95e43d941373d98aa3a375e5a3c6a2))
* complete master client data migrations, layout updates, and excel export features ([24df16b](https://github.com/bphndigitalservice/fungsionalpro/commit/24df16b24b31789e366c2e491079721af5f18cf7))
* filter, ([c48bb5f](https://github.com/bphndigitalservice/fungsionalpro/commit/c48bb5f38653477c1820f817cdfaa8d317599ec2))
* implement in-app notifications for JF activity verification with status colors and link redirect ([ff192c6](https://github.com/bphndigitalservice/fungsionalpro/commit/ff192c6c20ddea8ffe7985e6e562ad971badbaf9))
* migrate matching service to use MasterJf model with raw text parsing ([f2dcefd](https://github.com/bphndigitalservice/fungsionalpro/commit/f2dcefd002457fa08a1d983b34815717ffaf4943))
* point addition ([85a6112](https://github.com/bphndigitalservice/fungsionalpro/commit/85a6112696f27ef296444f593be9f5a6f76dbb9f))
* roadrunner ([f5ab80d](https://github.com/bphndigitalservice/fungsionalpro/commit/f5ab80df302dac33b870659b22bf514afb8f6ef9))

# Changelog

All notable changes to this project will be documented in this file.
This file is maintained by semantic-release.
