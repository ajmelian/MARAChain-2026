# Version Policy

## Current Version

```
Version:      1.2.0
Release Date: 2026-07-14
Status:       Development (pre-alpha)
Codename:     Genesis
```

## Schema

MARAChain adheres to [Semantic Versioning 2.0.0](https://semver.org/).

```
MAJOR.MINOR.PATCH

MAJOR — Breaking changes: API incompatible, schema incompatible,
        migration not backward-compatible, removed functionality
MINOR — New features: backward-compatible functionality additions,
        new entities, new endpoints, new modules
PATCH — Bug fixes: backward-compatible fixes, security patches,
        performance improvements, documentation updates
```

### Pre-release Labels (future)

| Label | Meaning |
|-------|---------|
| `alpha` | Feature-incomplete, internal testing only |
| `beta` | Feature-complete, external testing, known bugs acceptable |
| `rc` | Release candidate, production-ready pending final validation |

### Examples

```
1.1.1-alpha.1   → First pre-alpha build
1.1.1-beta      → First beta release
1.1.1-rc.1      → First release candidate
1.2.0           → New features, backward-compatible
2.0.0           → Breaking changes
```

## Release Process

1. **Development**: all work in feature branches (`feature/*`)
2. **Integration**: merged to `develop` after code review + CI pass
3. **Release branch**: `release/X.Y.Z` created from `develop`
   - `CHANGELOG.md` updated with all entries under `[Unreleased]`
   - `VERSION.md` updated with new version and date
   - Version bump in `project.openspec.yaml`
4. **Tag**: `git tag -a vX.Y.Z -m "Release vX.Y.Z"` on `main`
5. **Merge**: release branch → `main` + `develop`
6. **Deploy**: production deployment from `main` tag

## Version History

| Version | Date | Status | Highlights |
|---------|------|--------|------------|
| [1.2.0](./CHANGELOG.md#120---2026-07-14) | 2026-07-14 | Pre-alpha | SHIELD auth, web frontend (Bootstrap 5 + Alpino), service layer, CLI commands, rate limiting, health check |
| [1.1.1](./CHANGELOG.md#111---2026-07-13) | 2026-07-13 | Pre-alpha | 9 entities, 9 migrations, 9 models, 9 controllers, 164 tests |
| [1.0.0](./CHANGELOG.md#100---2026-07-13) | 2026-07-13 | Initial | Project bootstrap, specs, roadmap |

## Tag Conventions

```
v1.1.1          → Full release
v1.1.1-alpha.1  → Pre-release
v1.1.1-beta     → Beta
v1.1.1-rc.1     → Release candidate
```

## Consistency Checks

- `VERSION.md` version **must** match `CHANGELOG.md` latest version
- `VERSION.md` version **must** match `project.openspec.yaml` version
- Git tag **must** match `VERSION.md` version (prefixed with `v`)
- `CHANGELOG.md` `[Unreleased]` section is cleared when releasing
- Release date in `CHANGELOG.md` and `VERSION.md` **must** match
