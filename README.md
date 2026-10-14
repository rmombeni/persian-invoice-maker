# فاکتورساز فارسی | Persian Invoice Maker 🧾

> اسکریپت رایگان و متن‌باز **ساخت و صدور فاکتور فارسی** — فاکتور آنلاین راست‌به‌چپ با تاریخ شمسی، شماره فاکتور خودکار، محاسبه‌ی مالیات بر ارزش افزوده، چاپ و خروجی PDF. بدون دیتابیس، فقط یک فایل PHP.
>
> **Free & open-source Persian (Farsi) invoice maker** — a single-file PHP RTL invoice generator with Jalali date numbering, VAT calculation, print themes & PDF export. No database, no dependencies.

![نمای فاکتورساز فارسی — Persian invoice maker screenshot](docs/screenshot.png)

## فاکتورساز چیست؟

«فاکتورساز فارسی» یک ابزار سبک و تک‌فایلی برای **صدور فاکتور و صورتحساب حرفه‌ای** است که برای فریلنسرها، آژانس‌های طراحی سایت، شرکت‌های هاستینگ و کسب‌وکارهای کوچک ایرانی ساخته شده. برخلاف نرم‌افزارهای حسابداری سنگین، کافیست یک فایل `index.php` را روی هاست کپی کنید و در چند ثانیه اولین فاکتور خود را بسازید — بدون نصب، بدون پایگاه‌داده، بدون ثبت‌نام.

همه‌چیز روی خود فاکتور **به‌صورت زنده قابل ویرایش** است: سربرگ با لوگوی شرکت، اطلاعات مشتری، ردیف‌های کالا و خدمات با درگ‌اند‌دراپ، تخفیف و مالیات، و در انتها چاپ فاکتور یا دانلود PDF با ۹ قالب زیبا. فاکتورها با **شماره‌ی خودکار بر اساس سال شمسی** (مثل `1405-1001`) صادر و در یک فایل JSON ذخیره می‌شوند تا آرشیو کاملی با جستجو داشته باشید.

## ✨ امکانات فاکتورساز

- 📝 **ویرایش زنده‌ی فاکتور** — عنوان‌ها، ردیف‌ها و مبالغ مستقیم روی صورتحساب ویرایش می‌شوند
- 🔢 **شماره فاکتور خودکار شمسی** — شماره‌گذاری «سال شمسی-ردیف» (مثل `1405-1001`) با تبدیل دقیق تاریخ میلادی به جلالی
- 🧩 **قالب‌های آماده‌ی ردیف** — هاستینگ، طراحی وب‌سایت، ثبت دامنه، گواهی SSL و پشتیبانی ماهانه
- 🖱️ **درگ‌اند‌دراپ ردیف‌ها** + مشخصات فنی با آیکون خودکار و جمع جزئی هر ۳ ردیف
- 💰 **محاسبه‌ی خودکار** — تخفیف درصدی، مالیات بر ارزش افزوده و تبدیل مبلغ نهایی **به حروف فارسی**
- 💳 **مدیریت پرداخت** — مبلغ پرداخت‌شده، شناسه‌ی پیگیری، لینک پرداخت با **کد QR** و وضعیت خودکار فاکتور (پرداخت‌شده / پرداخت جزئی / در انتظار / معوق / لغو شده)
- 🔒 **قفل ویرایش** — فاکتور پرداخت‌شده برای جلوگیری از تغییر اشتباهی قفل می‌شود
- 🗂️ **آرشیو و جستجوی فاکتورها** — ذخیره، بارگذاری، کپی و حذف صورتحساب‌ها (ذخیره‌سازی در `invoices.json`)
- 🖨 **چاپ و دانلود PDF** — ۹ قالب چاپی: کلاسیک، رسمی/اداری، شرکتی سرمه‌ای، مینیمال، مدرن بنفش، مدرن فیروزه‌ای، بولد مشکی‌سفید، گرم و تیره‌ی طلایی
- 🌙 **حالت تاریک**، فونت وزیرمتن، اعداد فارسی و پشتیبانی از تومان / ریال / دلار
- 🏢 **سربرگ اختصاصی** — لوگو، مهر و امضا با آپلود تصویر

## 🚀 نصب و اجرا

تنها پیش‌نیاز **PHP 7.4+** است — بدون دیتابیس، بدون Composer، بدون نصب:

```bash
php -S localhost:8000
```

سپس `http://localhost:8000` را باز کنید. روی هاست اشتراکی (cPanel / دایرکت‌ادمین) هم کافیست فایل `index.php` را در یک پوشه کپی کنید؛ فاکتورها در فایل `invoices.json` کنار همان اسکریپت ذخیره می‌شوند.

## ❓ سوالات متداول

**فاکتورساز رایگان است؟**
بله — کاملاً رایگان و متن‌باز تحت لایسنس MIT.

**برای ساخت فاکتور به دیتابیس نیاز دارم؟**
خیر. همه‌چیز در یک فایل JSON ساده ذخیره می‌شود.

**چطور از فاکتور PDF بسازم؟**
دکمه‌ی «دانلود PDF» یا «چاپ صورت‌حساب» را بزنید و یکی از ۹ قالب چاپی را انتخاب کنید.

**روی هاست اشتراکی کار می‌کند؟**
بله؛ هر هاستی که PHP داشته باشد کافیست.

## ⚠️ نکته امنیتی

- فایل `invoices.json` حاوی اطلاعات فاکتورها و مشتریان است و در گیت نادیده گرفته شده؛ آن را منتشر نکنید.
- این ابزار احراز هویت ندارد؛ اگر آن را روی هاست عمومی می‌گذارید، پوشه را با رمز محافظت کنید (مثلاً `.htpasswd`).

---

## 🇬🇧 English

**Persian Invoice Maker** is a free, open-source, single-file PHP app for creating professional **RTL Persian (Farsi) invoices** — perfect for freelancers, web agencies, hosting providers and small businesses.

### Features

- Live-editable invoices: company letterhead with logo, customer info, drag-and-drop line items
- **Automatic Jalali (Shamsi) invoice numbering** (e.g. `1405-1001`) with a pure-PHP Gregorian→Jalali conversion
- Ready-made item templates (hosting, web design, domain, SSL, maintenance)
- Discount %, **VAT calculation**, and the grand total spelled out **in Persian words**
- Payment tracking: paid amount, transaction ID, payment link with **QR code**, auto status badge (paid / partial / pending / overdue / canceled), edit-lock for paid invoices
- Invoice archive with search (flat-file storage in `invoices.json`)
- **Print & PDF export** with 9 beautiful print themes
- Dark mode, Vazirmatn font, Persian digits, toman / rial / dollar currencies

### Quick start

```bash
php -S localhost:8000
```

Then open `http://localhost:8000`. On shared hosting, just drop `index.php` into a folder — invoices are stored in `invoices.json` next to the script. No database required.

## 🌐 سازنده | Made by

ساخته‌شده توسط **[تایمیت — timit.ir](https://timit.ir/)** — مرجع آموزش وردپرس فارسی و خدمات طراحی سایت، سئو و دیجیتال مارکتینگ. 💙

Built by **[Timit — timit.ir](https://timit.ir/)** — Persian WordPress education, web design, SEO & digital marketing.

## 📄 لایسنس | License

MIT — see [LICENSE](LICENSE).

---

<sub>کلیدواژه‌ها: فاکتورساز، فاکتور آنلاین، فاکتور فارسی، صدور فاکتور، صورتحساب، پیش‌فاکتور، فاکتور رایگان، فاکتور php، چاپ فاکتور، فاکتور pdf، مالیات بر ارزش افزوده، تاریخ شمسی — Keywords: persian invoice maker, farsi invoice generator, RTL invoice, PHP invoice script, invoice PDF, Jalali invoice, toman, self-hosted invoicing</sub>
