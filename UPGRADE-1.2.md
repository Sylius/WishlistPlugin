# UPGRADE FROM 1.1 TO 1.2

## PostgreSQL support

PostgreSQL is now officially supported. A new PostgreSQL-specific migration has been added.

### If you were already using PostgreSQL

The initial PostgreSQL migration will detect existing tables and skip creation automatically.
Run migrations as usual:

```bash
bin/console doctrine:migrations:migrate
```
