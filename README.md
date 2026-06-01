# BrickPHP
A Server-Powered Web Applications (Brick) framework using PHP.

There are 3 different sub-projects in this repository:
- 'Brick' - the main framework for building server and client-powered web applications in PHP.
- 'CodeQuery' - a way to write statically typed SQL in PHP. 
- 'Brick-UI' - a alternative to traditional templating engines for PHP. It builds on fluent API and programmatic UI generation. Layout and styling is based on 

# setup

```bash
docker compose up

# or to rebuild
docker compose build

# refresh composer autoload
# or restart the container. Compose install runs on startup
docker exec -it php-apache composer dump-autoload
```

