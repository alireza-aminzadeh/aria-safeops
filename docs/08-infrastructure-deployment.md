# ۸) زیرساخت و استقرار — Aria SafeOps

## ۸.۱ مشخصات دقیق سرور (بررسی‌شده از طریق SSH — ۱۴۰۵/۰۶/۱۴)

| مورد | مقدار واقعی |
|---|---|
| IP | `91.107.130.11` |
| Hostname | `hse` |
| OS | Ubuntu 26.04.1 LTS "Resolute Raccoon" |
| Kernel | `7.0.0-30-generic` |
| CPU | ۲ vCPU |
| RAM کل | ۳.۷ GiB (`free -h`) |
| RAM آزاد در زمان بررسی | ۳.۳ GiB |
| Swap | ۰ (توصیه: افزودن ۱ گیگ swap به‌عنوان ضامن OOM) |
| دیسک | `/dev/sda1` — ۳۸ گیگ کل، ۳۵ گیگ آزاد |
| Docker | نصب نشده |
| Git | نصب شده (`/usr/bin/git`) |
| UFW | نصب شده، غیرفعال |
| پورت‌های باز | فقط `22` |

## ۸.۲ بودجهٔ حافظهٔ کانتینرها (فاز ۱)

از ~۳۷۰۰ مگ، حدود ۴۰۰–۵۰۰ مگ صرف کرنل/systemd/sshd می‌شود → بودجهٔ واقعی کانتینرها ≈ **۳.۲ گیگ**.

| سرویس | Limit | Reservation |
|---|---|---|
| nginx | ۹۶M | ۴۸M |
| app (Symfony/PHP-FPM) | ۶۴۰M | ۳۲۰M |
| postgres (+TimescaleDB) | ۷۶۸M | ۳۸۴M |
| redis | ۲۰۰M | ۱۰۰M |
| **جمع فعلی** | **۱۷۰۴M (~1.7GB)** | — |
| حاشیهٔ باقی‌مانده برای رشد (Mercure فاز ۲ ~۱۲۸M و…) | ~۱.۵ گیگ | — |

## ۸.۳ نصب Docker روی سرور (Ubuntu 26.04)

```bash
# به‌روزرسانی پایه
apt-get update && apt-get upgrade -y

# نصب پیش‌نیازها
apt-get install -y ca-certificates curl gnupg

# افزودن مخزن رسمی Docker
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  tee /etc/apt/sources.list.d/docker.list > /dev/null

apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# تست
docker --version
docker compose version
```

### افزودن Swap ایمنی (۱ گیگ — توصیه‌شده چون سرور Swap ندارد)
```bash
fallocate -l 1G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

### فعال‌سازی UFW
```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

## ۸.۴ استقرار اولیه (اسکریپت‌های `infra/scripts`)

```bash
# روی سرور به‌عنوان root
bash /tmp/provision-host.sh
bash /opt/aria-safeops/infra/scripts/generate-env.sh   # یا از روی ریپوی کلون‌شده
bash /opt/aria-safeops/infra/scripts/clone-repo.sh
sudo -u deploy -H docker compose -f /opt/aria-safeops/docker-compose.yml up -d
```

Migration و `app:seed` هنگام استارت کانتینر (`app-prod.sh`) اجرا می‌شوند. ورود Production: نام کاربری `alireza`.

## ۸.۵ گواهی SSL (Let's Encrypt)
DNS زیردامنهٔ `hse.aria-ai.ir` باید به `91.107.130.11` اشاره کند. Nginx تا قبل از وجود فایل گواهی فقط HTTP سرو می‌کند (`http.conf`). صدور گواهی:

```bash
bash /opt/aria-safeops/infra/scripts/issue-tls.sh
```

## ۸.۶ Local Dev روی Docker Desktop (ویندوز)
مطابق بررسی محلی: Docker Desktop نصب و در حال اجراست (`Docker version 29.7.2`، حدود ۴ گیگ رم به VM لینوکس تخصیص داده شده — **این تصادفاً بسیار نزدیک به رم واقعی سرور Production است، خوب برای شبیه‌سازی رفتار حافظه**).

```powershell
cd c:\development\aria-ai-oil-gas\aria-safeops
Copy-Item .env.example .env
# ویرایش .env با مقادیر dev (رمزهای ساده برای local کافی است)
docker compose up --build -d
docker compose exec app php bin/console doctrine:migrations:migrate
php bin/console app:seed --no-interaction   # اگر PHP روی میزبان اجرا می‌شود: کاربر alireza / alireza
```

> توجه: مسیرهای Volume در `docker-compose.override.yml` با `./` نسبی نوشته شده‌اند (نه `C:\...`) — طبق قاعدهٔ پروژه برای هم‌خوانی کامل با لینوکس Production.

## ۸.۷ استراتژی بک‌آپ
اسکریپت `infra/scripts/backup-postgres.sh` هر شب ساعت ۰۲:۱۵ روی سرور اجرا می‌شود (`/var/backups/aria-safeops`، نگه‌داری ۷ روز). انتقال به مقصد خارج از سرور (Storage Box هتزنر) هنوز دستی است.
