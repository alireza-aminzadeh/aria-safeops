کلیدهای JWT در این پوشه تولید می‌شوند (`private.pem` / `public.pem`) و gitignore هستند.

```bash
php bin/console lexik:jwt:generate-keypair --skip-if-exists
```
