---
name: config
description: Configuration settings for the application. Use when working with writing, editing or deleting configuration values in the /config folder, or manipulating config() or env() variables.
---

# Config

## Rules

- You MUST stick to the existing config structure and conventions.
- You MUST only call `config()` in the code, and you MUST NOT call `env()` outside of config files.
- You MUST write config values dedicated to Monica in the `config/monica.php` file.
- You MUST document config values in `config/*.php` following Laravel's conventions: 3 lines of comments per value.
- You MUST document config values in `.env.example`.
