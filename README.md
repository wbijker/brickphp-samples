# BrickPHP Samples

The showcase site, sample apps, and Docker dev stack for the
[BrickPHP](https://github.com/wbijker/brickphp) framework.

Contents:

- `www/` — the documentation / landing site (entry point `index.php`).
- `samples/` — runnable example apps: `Docs`, `News`, `SiteApp`, `TodoApp`.
- `codequery/`, `heroicons/` — supporting libraries used by the samples.
- `Dockerfile`, `docker-compose.yml`, `xdebug.ini` — local dev stack.

## Layout

This repo expects the framework library to be checked out as a sibling:

```
~/projects/
├── brickphp/          ← github.com/wbijker/brickphp        (the library)
└── brickphp-samples/  ← github.com/wbijker/brickphp-samples (this repo)
```

The `path` Composer repository in `www/composer.json` and the
`../brickphp` volume mount in `docker-compose.yml` both rely on that
relative layout. Once `brickphp/brickphp` is released to Packagist,
the `path` repository will be removed and the layout requirement
goes away.

## Setup

```bash
cd ~/projects
git clone git@github.com:wbijker/brickphp.git
git clone git@github.com:wbijker/brickphp-samples.git
cd brickphp-samples
docker compose up
```

Visit http://localhost:8000.

```bash
# Rebuild the image if you change the Dockerfile
docker compose build

# After editing composer.json (host-side install), refresh autoload inside the container
docker exec -it php-apache composer dump-autoload
```

The container's `CMD` runs `composer dump-autoload` on every boot, so a
plain `docker compose restart` is usually enough after host-side
composer changes.
